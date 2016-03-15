<?php

namespace Urlpin\Helper\Element\Xxx;

class Extremetube extends \Urlpin\Helper\AbstractElement {
	
	public static function getRegularExpresions() {
		$self = new self();
		return array(
			'https?://((.*).)?extremetube\.com/?' => $self,
		);
	}
	
	private function replace($src) {
		return preg_replace_callback('~(https?://((.*).).extremetube.phncdn.com/)([^/]*)/([^/]*)([^=]*)$~',function($m) {
			return rtrim($m[1],'/') . $m[6];
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
					
					if(preg_match('~https?://(?:www\.)?extremetube\.com/video/(.*)/?~i', self::$url, $match)) {
						$html = @file_get_contents(self::$url . '?ajaxhtml5=1');
						if($html) {
							$dom2 = new \Core\Dom\Query($html);
							$video = $dom2->query('video');
							if($video->count()) {
								$src = $video->getElement(0)->getAttribute('poster');
								if(preg_match('~(https?://((.*).).extremetube.phncdn.com/)([^/]*)/([^/]*)([^=]*)$~', $src, $m)) {
									if(is_array( $imgInfo = $this->checkIsImage(rtrim($m[1],'/') . $m[6]) ) ) {
										if($title) { $imgInfo['title'] = $title; }
										$return['images'][$imgInfo['image']] = $imgInfo;
									}
								}
							}
						}
						
					} else {
						$grid = $this->dom->query('div.video-box-wrapper');
						
						if( ($total = $grid->count()) > 0 ) {
							for($i=0; $i<$total; $i++) {
								$box = $grid->get($i);
								$image = $box->query('a img');
								
								if($image->count() && is_array( $imgInfo = $this->checkIsImage( $this->replace($image->getElement(0)->getAttribute('src')) ) ) ) {
									$description = $box->query('h2.title a');
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