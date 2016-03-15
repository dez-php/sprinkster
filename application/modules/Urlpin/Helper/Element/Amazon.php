<?php

namespace Urlpin\Helper\Element;

class Amazon extends \Urlpin\Helper\AbstractElement {
	
	public static function getRegularExpresions() {
		$self = new self();
		return array(
			'https?://((.*).)?amazon\.com/?' => $self,
		);
	}
	
	protected function getMaxImage($images) {
		$defsize = false;
		$max = 0;
		foreach($images AS $image => $size) {
			$dim = $size[0]*$size[1];
			if($max < $dim) {
				$max = $dim;
				$defsize = array(
					'image' => $image,
					'width' => $size[0],
					'height' => $size[1]		
				);
			}
		}
		return $defsize;
	}
	
	private function replace($src) {
		return str_ireplace(
					array('_SS100_','_SS40_','_SL133_','_SX425_','_SY300_','small.'), 
					array('_SX522_','_SX522_','_SX522_','_SX522_','_SX522_','')
				, $src);
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
					
					if(preg_match('~https?://((.*).)?amazon\.com(.*)?/dp/(.*)/?~i', self::$url, $match)) {
						
						$priceDom = $this->dom->query('#priceblock_ourprice, #actualPriceValue .priceLarge');
						$price = 0;
						if($priceDom->count()) {
							for($e=0; $e<$priceDom->count(); $e++) {
								$pricea = trim($priceDom->getElement($e)->nodeValue);
								if($pricea) {
									$price = $this->extractPrice($pricea);
									break;
								}
							}
						}
						
						$image = $this->dom->query('#imgTagWrapperId img');
						if($image->count()) {
							$dinamic = json_decode($image->getElement(0)->getAttribute('data-a-dynamic-image'), true);
							if(is_array($dinamic)) {
								$image = $this->getMaxImage($dinamic);
								if($image) {
									$return['images'][] = array(
										'width' => $image['width'],
										'height' => $image['height'],
										'title' => $title?$title:basename($image['image']),
										'mime' => \Core\File\Ext::getMimeFromFile($image['image']),
										'image' => $image['image'],
										'url' => self::$url,
										'price' => $price
									);
								}
							}
						} else {
							$image = $this->dom->query('#main-image');
							if($image->count() && is_array( $imgInfo = $this->checkIsImage( $this->replace($image->getElement(0)->getAttribute('src')) ) )) {
								$return['images'][] = array(
										'width' => $imgInfo['width'],
										'height' => $imgInfo['height'],
										'title' => $title?$title:basename($imgInfo['image']),
										'mime' => \Core\File\Ext::getMimeFromFile($imgInfo['image']),
										'image' => $imgInfo['image'],
										'url' => self::$url,
										'price' => $price
								);
							}
						}
						$images = $this->dom->query('#altImages li .a-button-text img');
						if( ($total = $images->count()) >0 ) {
							for($i=1; $i<$total; $i++) {
								$image = $images->getElement($i)->getAttribute('src');
								if($image) {
									$image = $this->replace($image);
									if(is_array( $imgInfo = $this->checkIsImage( $image ) ) ) {
										$imgInfo['title'] = $title;
										$imgInfo['url'] = self::$url;
										$imgInfo['price'] = $price;
										$return['images'][] = $imgInfo;
									}
								}
							}
						}
					}
					
					if(!$return['images']) {
						$parent = parent::result(false);
						if(isset($parent['images'])) {
							foreach($parent['images'] AS $img) {
								$src = $this->replace($img['image']);
								if(is_array( $imgInfo = $this->checkIsImage( $src ) ) ) {
									$img['image'] = $imgInfo['image'];
									$img['height'] = $imgInfo['height'];
									$img['width'] = $imgInfo['width'];
								}
								if($this->isAllowSize($img['width'], $img['height'])) {
									$return['images'][] = $img;
								}
							}
						}
					
						$parent = parent::result(false);
						if(isset($parent['images'])) {
							foreach($parent['images'] AS $img) {
								if(!isset($return['images'][$img['url']])) {
									$src = $this->replace($img['image']);
									if(is_array( $imgInfo = $this->checkIsImage( $src ) ) ) {
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
					}
					
					return count($return['images']) ? $return : false;
				}
			}
		}
		return false;
	}
	
	
	
}