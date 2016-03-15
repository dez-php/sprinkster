<?php

namespace Urlpin\Helper\Element\Xxx;

class Tube8 extends \Urlpin\Helper\AbstractElement {
	
	public static function getRegularExpresions() {
		$self = new self();
		return array(
			'https?://((.*).)?tube8\.com/?' => $self,
		);
	}
	
	private function replace($src) {
		return preg_replace_callback('~https?://((.*).).tube8.phncdn.com/(.*)/([\d]{1,})x([\d]{1,})/(.*)$~',function($m) {
			return 'http://'.$m[1].'.tube8.phncdn.com/' . $m[3] . '/240x180/' . $m[6];
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
					
					if(preg_match('~https?://(?:www\.)?tube8\.com/(.*)~i', self::$url, $match)) {
						if(preg_match('~var flashvars =(.*);~imsU', $this->dom->getDocument(), $match)) {
							if( is_array( $result = json_decode($match[1], true) ) ) {
								$result = array_map('urldecode', $result);
								if(isset($result['image_url']) && is_array( $imgInfo = $this->checkIsImage( $result['image_url'] ) ) ) {
									if($title) { $imgInfo['title'] = $title; }
									$return['images'][$imgInfo['image']] = $imgInfo;
								}
							}
						} else {
							$grid = $this->dom->query('.box-thumbnail');
							
							if( ($total = $grid->count()) > 0 ) {
								for($i=0; $i<$total; $i++) {
									$box = $grid->get($i);
									$image = $box->query('.videoThumbs, .staticThumbs');
							
									if($image->count() && is_array( $imgInfo = $this->checkIsImage( $this->replace($image->getElement(0)->getAttribute('data-thumb')) ) ) ) {
										$description = $box->query('.sh2 a');
										if($description->count()) {
											$imgInfo['title'] = trim($description->getElement(0)->getAttribute('title'));
											$imgInfo['url'] = \Core\Url\Format::toAbsolute(self::$url, $description->getElement(0)->getAttribute('href'));
										}
										$return['images'][$imgInfo['image']] = $imgInfo;
									}
								}
							}
						}
						
					} else {
						$grid = $this->dom->query('.box-thumbnail');
						
						if( ($total = $grid->count()) > 0 ) {
							for($i=0; $i<$total; $i++) {
								$box = $grid->get($i);
								$image = $box->query('.videoThumbs, .staticThumbs');
								
								if($image->count() && is_array( $imgInfo = $this->checkIsImage( $this->replace($image->getElement(0)->getAttribute('data-thumb')) ) ) ) {
									$description = $box->query('.sh2 a');
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