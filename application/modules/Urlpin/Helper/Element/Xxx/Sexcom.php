<?php

namespace Urlpin\Helper\Element\Xxx;

class Sexcom extends \Urlpin\Helper\AbstractElement {
	
	public static function getRegularExpresions() {
		$self = new self();
		return array(
			'https?://((.*).)?sex\.com/?' => $self,
		);
	}
	
	private function replace($src) {
		return preg_replace('~%20$~','',str_ireplace(
					array('/236/'), 
					array('/620/')
				, $src));
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
					
					if(preg_match('~http://(?:www\.)?sex.com/picture/(.*)?~i', self::$url, $match)) {
						$image = $this->dom->query('link[rel="image_src"]');
						if($image->count() && is_array( $imgInfo = $this->checkIsImage($image->getElement(0)->getAttribute('href')) ) ) {
							if($title) { $imgInfo['title'] = $title; } 
							$return['images'][$imgInfo['image']] = $imgInfo;
						}
						
					} else {
						$grid = $this->dom->query('.masonry_box');
						
						if( ($total = $grid->count()) > 0 ) {
							for($i=0; $i<$total; $i++) {
								$box = $grid->get($i);
								$image = $box->query('.image_wrapper img');
								
								if($image->count() && is_array( $imgInfo = $this->checkIsImage( $this->replace($image->getElement(0)->getAttribute('src')) ) ) ) {
									$description = $box->query('.pin_info .title');
									if($description->count()) {
										$imgInfo['title'] = trim($description->getElement(0)->nodeValue);
									}
									$image = $box->query('.image_wrapper');
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