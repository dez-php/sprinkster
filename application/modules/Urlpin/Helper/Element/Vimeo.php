<?php

namespace Urlpin\Helper\Element;

class Vimeo extends \Urlpin\Helper\AbstractElement {
	
	public static function getRegularExpresions() {
		$self = new self();
		return array(
			'https?://((.*).)?vimeo\.com/?' => $self,
		);
	}
	
	public function result($dom = TRUE) {
		if( $this->linkIsImage ) {
			if(isset($this->imagesInfo[self::$url])) {
				return array(
						'title' => $this->imagesInfo[self::$url]['title'],
						'images' => array($this->imagesInfo[self::$url]),
						'response' => 200,
						'charset' => $this->charset
				);
			}
		} else {
			$this->setDom();
			if( !$this->getError() ) {
				if($this->dom instanceof \Core\Dom\Query) {
					$title = '';
					if(preg_match('/<title>(.*)<\/title>/sim', $this->dom->getDocument(), $match )) {
						$title = $match[1];
					}
					
					$return = array(
						'title' => $title,
						'response' => 200,
						'images' => array(),
						'charset' => $this->charset
					);
					
					if(preg_match('~https?://(?:www\.)?vimeo\.com/(channels/([^/]*)/)?([0-9]{1,12})$~i', self::$url, $match)) {
						$http = new \Core\Http\Curl();
						$http->setUseragent($this->request->getServer('HTTP_USER_AGENT')?$this->request->getServer('HTTP_USER_AGENT'):'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.9) Gecko/20071025 Firefox/2.0.0.9');
						$http->setReferrer(self::$url);
						$http->useCurl(function_exists('curl_init'));
						$http->setTimeout(1);
						$http->execute('http://vimeo.com/api/oembed.json?url=' . urlencode(self::$url));
						if(!$http->getError()) {
							$data = json_decode($http->getResult(), true);
							if($data) {
								$return['images'][$data['thumbnail_url']] = array(
									'width' => $data['thumbnail_width'],
									'height' => $data['thumbnail_height'],
									'title' => $data['title'],
									'mime' => \Core\File\Ext::getMimeFromFile($data['thumbnail_url']),
									'image' => $data['thumbnail_url'],
									'url' => self::$url,
									'price' => 0
								);
							}
						}
					} else {
						$grid = $this->dom->query('a');
						if( ($total = $grid->count()) > 0 ) {
							for($i=0; $i<$total; $i++) {
								$link = $grid->getElement($i)->getAttribute('href');
								if(preg_match('~/(channels/([^/]*)/)?([0-9]{1,12})~i', $link)) {
									$link = \Core\Url\Format::toAbsolute(self::$url, $link);
									$http = new \Core\Http\Curl();
									$http->setUseragent($this->request->getServer('HTTP_USER_AGENT')?$this->request->getServer('HTTP_USER_AGENT'):'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.9) Gecko/20071025 Firefox/2.0.0.9');
									$http->setReferrer(self::$url);
									$http->useCurl(function_exists('curl_init'));
									$http->setTimeout(1);
									$http->execute('http://vimeo.com/api/oembed.json?url=' . urlencode($link));
									if(!$http->getError()) {
										$data = json_decode($http->getResult(), true);
										if($data) {
											$return['images'][$data['thumbnail_url']] = array(
												'width' => $data['thumbnail_width'],
												'height' => $data['thumbnail_height'],
												'title' => $data['title'],
												'mime' => \Core\File\Ext::getMimeFromFile($data['thumbnail_url']),
												'image' => $data['thumbnail_url'],
												'url' => $link,
												'price' => 0
											);
										}
									}
								}
							}
						}
					}
					
					$parent = parent::result(false);
					if(isset($parent['images'])) {
						foreach($parent['images'] AS $img) {
							if(!isset($return['images'][$img['image']])) {
								if(is_array( $imgInfo = $this->checkIsImage( $img['image'] ) ) ) {
									$img['image'] = $imgInfo['image'];
									$img['height'] = $imgInfo['height'];
									$img['width'] = $imgInfo['width'];
								}
								if(!isset($return['images'][$img['image']])) {
									$return['images'][$img['image']] = $img;
								}
							}
						}
					}
					
					return count($return['images']) ? $return : false;
				}
			}
		}
		return false;
	}
	
}