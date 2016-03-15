<?php

namespace Urlpin\Helper\Element;

class Pinterest extends \Urlpin\Helper\AbstractElement {
	
	public static function getRegularExpresions() {
		$self = new self();
		return array(
			'https?://((.*).)?pinterest\.com/?' => $self,
			'https?://((.*).)?pinimg\.com/?' => $self,
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
					
					if(preg_match('~https?://((.*).)?pinterest\.com/pin/([\d]{1,})/?~i', self::$url, $match)) {
						$image = $this->dom->query('.PaddedPin .pinImage');
						if($image->count() && is_array( $imgInfo = $this->checkIsImage($image->getElement(0)->getAttribute('src')) ) ) {
							$alt = $image->getElement(0)->getAttribute('alt');
							if($alt) { $imgInfo['title'] = $alt; } 
							$return['images'][$imgInfo['image']] = $imgInfo;
						}
						
					} else {
						$grid = $this->dom->query('.item .pinWrapper');
						
						if( ($total = $grid->count()) > 0 ) {
							for($i=0; $i<$total; $i++) {
								$box = $grid->get($i);
								$image = $box->query('.pinImg');
								if($image->count() && is_array( $imgInfo = $this->checkIsImage( str_replace('/236x/', '/736x/', $image->getElement(0)->getAttribute('src')) ) ) ) {
									$description = $box->query('.pinDescription');
									if($description->count()) { $imgInfo['title'] = $description->getElement(0)->nodeValue; }
									$link = $box->query('.pinImageWrapper');
									if($link->count()) { 
										$link = \Core\Url\Format::toAbsolute(self::$url, $link->getElement(0)->getAttribute('href'));
										$imgInfo['url'] = $link;
									}
									$return['images'][$imgInfo['image']] = $imgInfo;
								}
							}
						}
					}
					
					$parent = parent::result(false);
					if(isset($parent['images'])) {
						foreach($parent['images'] AS $img) {
							if(!isset($return['images'][$img['image']])) {
								if(is_array( $imgInfo = $this->checkIsImage( str_replace(array('/236x/','/216x146/'), '/736x/', $img['image']) ) ) ) {
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