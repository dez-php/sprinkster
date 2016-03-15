<?php

namespace Youtubecrawler;

class CronController extends \Core\Base\Action {

    /*
     * @var \Google_Service_YouTube
     */
    private $api;

	public function init() {
		if(!$this->allow('youtubecrawler')) {
			exit;
		}
		$menuTable = new \Base\Menu();
		if(!$menuTable->countByModule('youtubecrawler')) {
			exit;
		}

        if(!\Base\Config::get('youtube_developer_key')) {
            exit;
        }
		
		$this->noLayout(true);
		set_time_limit(0);
		ignore_user_abort(true);

		include_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor/autoload.php';

        $client = new \Google_Client();
        $client->setDeveloperKey(\Base\Config::get('youtube_developer_key'));

        // Define an object that will be used to make all API requests.
        $this->api = new \Google_Service_YouTube($client);
	}
	
	public function indexAction() {
		$searchTable = new \Youtubecrawler\Search();
		$linkTable = new \Youtubecrawler\Link();
		$link = $searchTable->fetchRow(array('indexing = 0'),'id ' . (mt_rand(0, 1)?'ASC':'DESC'));
		if($link) {

            $limit = min(500,$link->limit);
            $step = 50;
            $pageToken = null;
            for($i = 0; $i < $limit; $i += $step) {
                $filter = [
                    'q' => $link->keyword,
                    'maxResults' => $step,
                    'order' => 'rating',
                    'videoEmbeddable' => 'true',
                    'safeSearch' => 'none',
                    'type' => 'video',
                ];
                if($pageToken) {
                    $filter['pageToken'] = $pageToken;
                }
                try {
                    $searchResponse = $this->api->search->listSearch('id,snippet', $filter);
                    $pageToken = $searchResponse->nextPageToken;
                    $items = $searchResponse->getItems();
                    if($items) {
                        foreach($items AS $item) {
                            $simple = $item->toSimpleObject();
                            if(!$linkTable->countByYoutubeId($simple->id['videoId'])) {
                                $new = $linkTable->fetchNew();
                                $new->youtube_search_id = $link->id;
                                $new->link = '';
                                $new->user_id = $link->user_id;
                                $new->category_id = $link->category_id;
                                $new->youtube_id = $simple->id['videoId'];
                                $new->date_added = \Core\Date::getInstance(null,\Core\Date::SQL_FULL,true)->toString();
                                $new->date_modified = $new->date_added;
                                $new->save();
                            }
                        }
                    }
                } catch(\Exception $e) {
                }
            }

			$link->indexing = 1;
			$link->date_modified = \Core\Date::getInstance(null,\Core\Date::SQL_FULL,true)->toString();
			$link->save();
		}
	}
	
	public function linksAction() {
		$linkTable = new \Youtubecrawler\Link();
		$searchTable = new \Youtubecrawler\Search();
		$pinTable = new \Pin\Pin();
		$videos = $linkTable->fetchAll(array('pin_id IS NULL','indexing = 0'),'id ' . (mt_rand(0, 1)?'ASC':'DESC'), 5);

        if($videos->count()) {
            foreach ($videos AS $video_link) {
                $videosResponse = $this->api->videos->listVideos('snippet,recordingDetails', array(
                    'id' => $video_link->youtube_id,
                ))->getItems();
                if($videosResponse) {
                    $video = array_shift($videosResponse)->toSimpleObject();

                    $from = $this->getLink('https://www.youtube.com/watch?v=' . $video_link->youtube_id);
                    if(!$from) {
                        $video_link->date_modified = \Core\Date::getInstance(null,\Core\Date::SQL_FULL,true)->toString();
                        $video_link->indexing = 6;
                        $video_link->save();
                        continue;
                    }

                    $thumb = $this->getThumb($from, $video);
                    if(!$thumb) {
                        $video_link->date_modified = \Core\Date::getInstance(null,\Core\Date::SQL_FULL,true)->toString();
                        $video_link->indexing = 7;
                        $video_link->save();
                        continue;
                    }

                    // get title
                    $title = isset($video->snippet['title'])?$video->snippet['title']:'';

                    //get description
                    $description = isset($video->snippet['description'])?$video->snippet['description']:'';

                    if($video_link->user_id) {
                        $user_id = $video_link->user_id;
                    } else {
                        //generate user
                        $user_id = $this->getAuthor($video);
                    }
                    if(!$user_id) {
                        $video_link->date_modified = \Core\Date::getInstance(null,\Core\Date::SQL_FULL,true)->toString();
                        $video_link->indexing = 8;
                        $video_link->save();
                        continue;
                    }

                    ////
                    $search = $searchTable->fetchRow(array('id = ?' => $video_link->youtube_search_id));

                    $_POST['extended']['tags'] = array();
                    if($search) {
                        $_POST['extended']['tags'][$search->keyword] = $search->keyword;
                    }
                    if(isset($video->snippet['tags']) && $video->snippet['tags']) {
                        foreach ($video->snippet['tags'] AS $c) {
                            $_POST['extended']['tags'][$c] = $c;
                        }
                    }

                    $sourceTable = new \Source\Source();
                    $new = $pinTable->fetchNew();
                    $new->user_id = $user_id;
                    $new->category_id = $video_link->category_id;
                    $new->source_id = $sourceTable->getSourceIdByLink($from);
                    $new->pinned_from = 'Pinned';
                    if($new->source_id) {
                        $new->from = $from;
                    }
                    $new->title = $this->escape($title);
                    $new->description = $this->escape($description);
                    $new->module = 'Urlpin';

                    $pinTable->getAdapter()->beginTransaction();
                    $image = \Base\Config::getUploadMethod('Base', 'pinThumbs');
                    try {
                        $pin_id = $new->save();
                        if($pin_id) {
                            //upload image
                            $image_path = '/pins' . \Core\Date::getInstance($new->date_added, '/yy/mm/', true);
                            if( is_array($image_info = $image->upload($thumb, $image_path)) ) {
                                $new->image = $image_info['file'];
                                $new->width = $image_info['width'];
                                $new->height = $image_info['height'];
                                $new->store = $image_info['store'];
                                $new->store_host = $image_info['store_host'];
                                $new->save();

                                $video_link->date_modified = \Core\Date::getInstance(null,\Core\Date::SQL_FULL,true)->toString();
                                $video_link->indexing = 1;
                                $video_link->pin_id = $pin_id;
                                $video_link->save();

                                //extend form
                                $forms = \Base\FormExtend::getExtension('pinForm.create');
                                foreach($forms AS $form) {
                                    if($form->save) {
                                        $saveName = $form->save;
                                        new $saveName(array('pin_id'=>$pin_id, 'parent'=>$this,'type'=>'create'));
                                    }
                                }
                                //end extend form

                                $pinTable->getAdapter()->commit();
                            } else {
                                $pinTable->getAdapter()->rollBack();
                                $video_link->date_modified = \Core\Date::getInstance(null,\Core\Date::SQL_FULL,true)->toString();
                                $video_link->indexing = 4;
                                $video_link->save();
                                $image->delete();
                            }
                        }
                    } catch (\Core\Exception $e) {
                        $pinTable->getAdapter()->rollBack();
                        $video_link->date_modified = \Core\Date::getInstance(null,\Core\Date::SQL_FULL,true)->toString();
                        $video_link->indexing = 3;
                        $video_link->save();
                        $image->delete();
                    }

                } else {
                    $video_link->date_modified = \Core\Date::getInstance(null,\Core\Date::SQL_FULL,true)->toString();
                    $video_link->indexing = 5;
                    $video_link->save();
                    continue;
                }
            }
        }
	}
	
	//////////////////////////////////////////////////////////
	
	protected function getThumb($from, $video) {
        if($from) {
            $helperImage = new \Urlpin\Helper\Element($from);
            $getedImages = @$helperImage->getAdapter()->result();
        }
        $images = isset($video->snippet['thumbnails']) ? $video->snippet['thumbnails'] : [];
        if(isset($getedImages['images']) && $getedImages['images']) {
            foreach($getedImages['images'] AS $img) {
                list($key) = explode('.', basename($img['image']));
                $images[$key] = [
                    'width' => $img['width'],
                    'height' => $img['height'],
                    'url' => $img['image']
                ];
            }
        }
        if(!$images)
            return;

        $thumbs = array();
        foreach($images AS $t) {
            $thumbs[$t['width']*$t['height']] = $t['url'];
        }
        krsort($thumbs);
        return array_shift($thumbs);
	}

    public function getAvatar($author) {
        if(isset($author->snippet['thumbnails'])) {
            if(isset($author->snippet['thumbnails']['high']))
                return $author->snippet['thumbnails']['high']['url'];
            if(isset($author->snippet['thumbnails']['medium']))
                return $author->snippet['thumbnails']['medium']['url'];
            if(isset($author->snippet['thumbnails']['default']))
                return $author->snippet['thumbnails']['default']['url'];
        }
        return null;
    }
	
	protected function getLink($link) {
        $media = new \Media\Helper\UrlInfo();
        if( $media->parseUrl($link) )
            return $link;
        return false;
	}
	
	protected function getAuthor($video) {
        if(!isset($video->snippet['channelId']))
            return false;
        try {
            $chanel = $this->api->channels->listChannels('snippet, contentOwnerDetails', [
                'id' => $video->snippet['channelId']
            ])->getItems();
            if($chanel) {
                $author = array_shift($chanel)->toSimpleObject();
                if(isset($author->snippet['title']) && $author->snippet['title']) {
                    $userTable = new \User\User();
                    $user = $userTable->fetchRow(array('email LIKE ?' => mb_strtolower($author->snippet['title'],'utf-8') . '@yt-cr.com'));
                    if(!$user) {
                        $user = $userTable->fetchNew();
                        $user->username = mb_strtolower($author->snippet['title'],'utf-8');
                        $user->email = mb_strtolower($author->snippet['title'],'utf-8') . '@yt-cr.com';
                        $user->password = md5(mb_strtolower($author->snippet['title'],'utf-8') . mt_rand(0000, 9999) . microtime());
                        $user->firstname = mb_strtolower($author->snippet['title'],'utf-8');
                        $user->lastname = mb_strtolower($author->snippet['title'],'utf-8');
                        $user->status = 1;
                        if($user->save()) {
                            $avatar = $this->getAvatar($author);
                            if($avatar) {
                                $image_path = '/users' . \Core\Date::getInstance($user->date_added, '/yy/mm/', true);
                                $image = \Base\Config::getUploadMethod('Base', 'userAvatars');
                                if( is_array($image_info = @$image->upload($avatar, $image_path)) ) {
                                    $user->avatar = $image_info['file'];
                                    $user->avatar_width = $image_info['width'];
                                    $user->avatar_height = $image_info['height'];
                                    $user->avatar_store = $image_info['store'];
                                    $user->avatar_store_host = $image_info['store_host'];
                                    $user->save();
                                }
                            }
                            return $user->id;
                        }
                    }
                }
            }
        } catch(\Exception $e) {}
        return false;
	}
	
}

?>