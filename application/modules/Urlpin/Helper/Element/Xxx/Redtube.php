<?php

namespace Urlpin\Helper\Element\Xxx;

class Redtube extends \Urlpin\Helper\AbstractElement {
	
	public static function getRegularExpresions() {
		$self = new self();
		return array(
			'http://(?:www\.)?redtube\.com/?' => $self,
		);
	}
	
	private function replace($src) {
		return preg_replace_callback('~https?://([^.]*).redtubefiles.com/_thumbs/(.*)/([\d]{1,})_([\d]{1,})([a-z]{1}).jpg$~',function($m) {
			return 'http://'.$m[1].'.redtubefiles.com/_thumbs/'.$m[2].'/'.$m[3].'_'.$m[4].'b.jpg';
		},$src);
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
					
					if(preg_match('~https?://(?:www\.)?redtube\.com/([0-9]{1,12})~i', self::$url, $match)) {
						$meta = $this->dom->query('meta[property="og:image"]');
						$has = false;
						if($meta->count()) {
							$src = $meta->getElement(0)->getAttribute('content');
							if(is_array( $imgInfo = $this->checkIsImage( $this->replace($src) ) ) ) {
								if($title) { $imgInfo['title'] = $title; }
								$return['images'][$imgInfo['image']] = $imgInfo;
								$has = true;
							}
						}
						if(!$has) {
							$src = 'http://img0'.mt_rand(1,4).'.redtubefiles.com/_thumbs/'.sprintf('%07d', substr($match[1],0,3)).'/'.sprintf('%07d', $match[1]).'/'.sprintf('%07d', $match[1]).'_012b.jpg';
							if(is_array( $imgInfo = $this->checkIsImage( $src ) ) ) {
								if($title) { $imgInfo['title'] = $title; }
								$return['images'][$imgInfo['image']] = $imgInfo;
							}
						}
						
					} else {
						$grid = $this->dom->query('ul.videoThumbs li');
						
						if( ($total = $grid->count()) > 0 ) {
							for($i=0; $i<$total; $i++) {
								$box = $grid->get($i);
								$image = $box->query('div.video img');
								if($image->count() && is_array( $imgInfo = $this->checkIsImage( $this->replace($image->getElement(0)->getAttribute('src')) ) ) ) {
									$description = $box->query('h2.videoTitle a');
									if($description->count()) {
										$imgInfo['title'] = trim($description->getElement(0)->getAttribute('title'));
										$imgInfo['url'] = \Core\Url\Format::toAbsolute(self::$url, $description->getElement(0)->getAttribute('href'));
									}
									$return['images'][$imgInfo['image']] = $imgInfo;
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