<?php

namespace Urlpin\Helper\Element\Xxx;

class Xvideos extends \Urlpin\Helper\AbstractElement {
	
	public static function getRegularExpresions() {
		$self = new self();
		return array(
// 			'https?://((.*).)?xvideos\.com/video([0-9]{1,12})' => $self,
			'https?://((.*).)?xvideos\.com/?' => $self,
		);
	}
	
	private function replace($src) {
		return preg_replace_callback('~https?://([^.]*).xvideos.com/videos/thumbs/(.*)$~',function($m) {
			return 'http://'.$m[1].'.xvideos.com/videos/thumbslll/'.$m[2];
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
					
					if(preg_match('~https?://(?:www\.)?xvideos\.com/video([0-9]{1,12})~i', self::$url, $match)) {

						$embed = $this->dom->query('#player embed');
						if($embed->count()) {
							$flashvars = $embed->getElement(0)->getAttribute('flashvars');
							if($flashvars) {
								$arr = array();
								parse_str (urldecode(html_entity_decode($flashvars)), $arr);
								if(isset($arr['url_bigthumb'])) {
									if(is_array( $imgInfo = $this->checkIsImage($arr['url_bigthumb']) ) ) {
										if($title) { $imgInfo['title'] = $title; }
										$return['images'][$imgInfo['image']] = $imgInfo;
									}
								}
							}
						}
						
					} else {
						$grid = $this->dom->query('.thumbBlock');
						
						if( ($total = $grid->count()) > 0 ) {
							for($i=0; $i<$total; $i++) {
								$box = $grid->get($i);
								$image = $box->query('.thumb a img');
								
								if($image->count() && is_array( $imgInfo = $this->checkIsImage( $this->replace($image->getElement(0)->getAttribute('src')) ) ) ) {
									$description = $box->query('p a');
									if($description->count()) {
										$imgInfo['title'] = trim($description->getElement(0)->nodeValue);
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