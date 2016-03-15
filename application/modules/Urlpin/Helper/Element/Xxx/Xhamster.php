<?php

namespace Urlpin\Helper\Element\Xxx;

class Xhamster extends \Urlpin\Helper\AbstractElement {
	
	public static function getRegularExpresions() {
		$self = new self();
		return array(
// 			'https?://((.*).)?xhamster\.com/movies/([0-9]{1,12})' => $self,
			'https?://((.*).)?xhamster\.com/?' => $self,
		);
	}
	
	private function replace($src) {
		return preg_replace_callback('~https?://([^.]*).xhcdn.com/t/(.*)/([\d]{1,})_([\d]{1,}).jpg$~',function($m) {
			return 'http://'.$m[1].'.xhcdn.com/t/'.substr($m[4],-3).'/'.$m[3].'_b_'.$m[4].'.jpg';
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
					
					if(preg_match('~https?://((.*).)?xhamster\.com/movies/([0-9]{1,12})~i', self::$url, $match)) {
						$image = 'http://et'.mt_rand(30,79).'.xhcdn.com/t/'.substr($match[3],-3).'/3_b_'.$match[3].'.jpg';
						if(is_array( $imgInfo = $this->checkIsImage($image) ) ) {
							if($title) { $imgInfo['title'] = $title; } 
							$return['images'][$imgInfo['image']] = $imgInfo;
						}
						
					} else {
						$grid = $this->dom->query('.video');
						
						if( ($total = $grid->count()) > 0 ) {
							for($i=0; $i<$total; $i++) {
								$box = $grid->get($i);
								$image = $box->query('img.thumb');
								
								if($image->count() && is_array( $imgInfo = $this->checkIsImage( $this->replace($image->getElement(0)->getAttribute('src')) ) ) ) {
									$description = $box->query('a u');
									if($description->count()) {
										$imgInfo['title'] = trim($description->getElement(0)->nodeValue);
									}
									$image = $box->query('a');
									if($image->count() && $image->getElement(0)->getAttribute('href')) {
										$imgInfo['url'] = \Core\Url\Format::toAbsolute(self::$url, $image->getElement(0)->getAttribute('href'));
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