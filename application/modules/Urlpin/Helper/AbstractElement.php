<?php

namespace Urlpin\Helper;

class AbstractElement {
	
	use \Local\Traits\Allow;

	/**
	 * @var string
	 */
	protected static $url;
	/**
	 * @var \Core\Dom\Query
	 */
	protected $dom;
	
	/**
	 * @var \Core\Http\Request
	 */
	protected $request;
	
	/**
	 * @var string
	 */
	protected $error;
	
	/**
	 * @var array
	 */
	protected $imagesInfo = array();
	
	/**
	 * @var bool
	 */
	protected $linkIsImage = false;
	
	protected $charset = 'utf-8';
	
	public function __construct() {
		$this->setRequest();
	}
	
	/**
	 * @return boolean|array
	 */
	public static function getRegularExpresions() { return false; }
	
	protected function isIgnore($src) {
		if(stripos($src, 'sprite') !== false) {
			return true;
		} else if( preg_match('~^(.*)load(.*).gif$~i', $src) ) {
			return true;
		}
		return false;
	}
	
	private function replace($src) {
		return str_ireplace(
					array('/d/l225/'), 
					array('/d/l450/')
				, $src);
	}
	
	/**
	 * @return boolean|string
	 */
	public function result($dom = true) {
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
			$dom?$this->setDom():false;
			if( !$this->getError() ) {
				if($this->dom instanceof \Core\Dom\Query) {
					
					$title = '';
					if(preg_match('/<title>(.*)<\/title>/sim', $this->dom->getDocument(), $match )) {
						$title = trim(strip_tags(html_entity_decode(\Core\Utf8::convert($match[1], $this->charset, 'utf-8'), ENT_QUOTES, 'utf-8')));
					}
					
					$return = array(
						'title' => $title,
						'response' => 200,
						'images' => array(),
						'charset' => $this->charset
					);
					
					$products = $this->dom->queryXpath('//*[@itemtype="http://schema.org/Product"]');	
					
					if( ($total = $products->count()) > 0 ) {
						
						for($i=0; $i<$total; $i++) {
							$product = $products->get($i);
							
							$images = $product->query('img[itemprop="image"]');
							$checkLarge = false;
							if($images->count()) {
								for($r=0; $r<$images->count(); $r++) {
									$self = $images->getElement($r);
									$src = \Core\Url\Format::toAbsolute(self::$url, $self->getAttribute('src'));
									$src = $this->replace($src);
									if( !$this->isIgnore($src) && is_array($imgInfo = $this->checkIsImage($src)) ) {
										if( $this->isAllowSize($imgInfo['width'], $imgInfo['height']) ) {
											$checkLarge = true;
											break;
										}
									}
								}
							}
							if(!$checkLarge) {
								$images = $product->query('img');
							}
							
							$element = $products->getElement($i);
							$href = null;
							if(strtolower($element->nodeType) == 'a') {
								$attr = $element->getAttribute('href');
								if($attr) {
									$href = \Core\Url\Format::toAbsolute(self::$url, $attr);
								}
							}
							
							if( ($total_img = $images->count()) > 0 ) {
								$price = 0;
								$priceDom = $product->query('*[itemprop="price"]');
								if($priceDom->count()) {
									$price = $this->extractPrice(trim($priceDom->getElement(0)->nodeValue));
								}
								
								for($r=0; $r<$total_img; $r++) {
									$self = $images->getElement($r);
									$src = \Core\Url\Format::toAbsolute(self::$url, $self->getAttribute('src'));
									$src = $this->replace($src);
									
									if( !$this->isIgnore($src) && is_array($imgInfo = $this->checkIsImage($src)) ) {
										if( $this->isAllowSize($imgInfo['width'], $imgInfo['height']) ) {
											$title2 = trim($self->getAttribute('alt'));
											$title2 = $title2 ? $title2 : trim($self->getAttribute('title'));
											$item_href = $product->query('*[itemprop="url"]');
											if(!$item_href->count()) {
												$item_href = $product->query('a');
											}
											if($item_href->count() && \Core\Form\Validator::clearHost( $item_href->getElement(0)->getAttribute('href') )) {
												$imgInfo['url'] = $item_href->getElement(0)->getAttribute('href');
												if(!$title2) {
													$title2 = trim($item_href->getElement(0)->getAttribute('title'));
												}
											}
											
											$item_name = $product->query('*[itemprop="name"]');
											if($item_name->count()) {
												$title2 = trim(strip_tags($item_name->getElement(0)->nodeValue));
											}
	
											$imgInfo['price'] = $price;
											$imgInfo['title'] = $title2 ? $title2 : ($title ? $title : $imgInfo['title']);
											$return['images'][$imgInfo['image']] = $imgInfo;
										}
									}
								}	
							}	
						}
						
					} else {
					
						$meta = $this->dom->query('meta[property="og:image"]');
						if($meta->count()) {
							$src = $meta->getElement(0)->getAttribute('content');
							$src = \Core\Url\Format::toAbsolute(self::$url, $src);
							if( is_array($imgInfo = $this->checkIsImage($src)) ) {
								if( $this->isAllowSize($imgInfo['width'], $imgInfo['height']) ) {
									$imgInfo['title'] = $title ? $title : $imgInfo['title'];
									$return['images'][$imgInfo['image']] = $imgInfo;
								}
							}
						}
						
						$meta = $this->dom->query('link[rel="image_src"]');
						if($meta->count()) {
							$src = $meta->getElement(0)->getAttribute('href');
							$src = \Core\Url\Format::toAbsolute(self::$url, $src);
							if( is_array($imgInfo = $this->checkIsImage($src)) ) {
								if( $this->isAllowSize($imgInfo['width'], $imgInfo['height']) ) {
									$imgInfo['title'] = $title ? $title : $imgInfo['title'];
									$return['images'][$imgInfo['image']] = $imgInfo;
								}
							}
						}
						
						$grid = $this->dom->query('img, *[@style]');
						
						if( ($total = $grid->count()) > 0 ) {
							for($i=0; $i<$total; $i++) {
								$self = $grid->getElement($i);
								$style = $self->getAttribute('style');
								if( strtolower($self->nodeName) == 'img' ) {
									$src = \Core\Url\Format::toAbsolute(self::$url, $self->getAttribute('src'));
									if( !$this->isIgnore($src) && is_array($imgInfo = $this->checkIsImage($src)) ) {
										if( $this->isAllowSize($imgInfo['width'], $imgInfo['height']) ) {
											$title2 = trim(strip_tags(html_entity_decode($self->getAttribute('alt'))));
											$title2 = $title2 ? $title2 : trim(strip_tags(html_entity_decode($self->getAttribute('title'))));
											if(!$title2) {
												$parent = $grid->getParentByTag('a', $self);
												if($parent) {
													if(\Core\Form\Validator::clearHost($parent->getAttribute('href'))) {
														$imgInfo['url'] = \Core\Url\Format::toAbsolute(self::$url, $parent->getAttribute('href'));
														if( is_array( $check = $this->checkIsImage($imgInfo['url']) ) ) {
															$check['url'] = self::$url;
															$imgInfo = $check;
														}
													}
													$title2 = $title2 ? $title2 : trim($self->getAttribute('title'));
												}
											}
											$imgInfo['title'] = $title2 ? $title2 : ($title ? $title : strip_tags(html_entity_decode($imgInfo['title'])));
											$return['images'][$imgInfo['image']] = $imgInfo;
										}
									}
								} else if( strtolower($self->nodeName) == 'a' && $style && preg_match('~background\-image:\s?url\([\'\"\s]?([^\)]*)[\'\"\s]?\);?~i', $style, $match) ) {
									if( !$this->isIgnore($match[1]) ) {
										$src = \Core\Url\Format::toAbsolute(self::$url, $match[1]);
										if( is_array($imgInfo = $this->checkIsImage($src)) ) {
											if( $this->isAllowSize($imgInfo['width'], $imgInfo['height']) ) {
												$title2 = trim($self->getAttribute('title'));
												if(\Core\Form\Validator::clearHost($self->getAttribute('href'))) {
													$imgInfo['url'] = \Core\Url\Format::toAbsolute(self::$url, $self->getAttribute('href'));
												}
												$imgInfo['title'] = $title2 ? $title2 : ( $title ? $title : strip_tags(html_entity_decode($imgInfo['title'])) );
												$return['images'][$imgInfo['image']] = $imgInfo;
											}
										}
									}
								} else if( $style && preg_match('~background\-image:\s?url\([\'\"\s]?([^\)]*)[\'\"\s]?\);?~i', $style, $match) ) {
									$src = \Core\Url\Format::toAbsolute(self::$url, $match[1]);
									if( !$this->isIgnore($match[1]) && is_array($imgInfo = $this->checkIsImage($src)) ) {
										if( $this->isAllowSize($imgInfo['width'], $imgInfo['height']) ) {
											$title2 = trim($self->getAttribute('alt'));
											$title2 = $title2 ? $title2 : trim($self->getAttribute('title'));
											if(!$title2) {
												$parent = $grid->getParentByTag('a', $self);
												if($parent) {
													if(\Core\Form\Validator::clearHost($parent->getAttribute('href'))) {
														$imgInfo['url'] = \Core\Url\Format::toAbsolute(self::$url, $parent->getAttribute('href'));
													}
													$title = $title2 ? $title2 : ($title ? $title : trim(strip_tags(html_entity_decode($self->getAttribute('title')))));
												}
											}
											$imgInfo['title'] = $title ? $title : $imgInfo['title'];
											$return['images'][$imgInfo['image']] = $imgInfo;
										}
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
	
	protected function extractPrice($price) {
		if(preg_match('~([\d]{1,})(.([\d]{1,}))?~ims', $price, $match)) {
			return $match[0];
		}
		return 0;
	}
	
	/**
	 * @return \Urlpin\Helper\AbstractElement
	 */
	protected function setRequest() {
		if(!$this->request) {
			$this->request = \Core\Http\Request::getInstance();
		}
		return $this;
	}
	
	/**
	 * @param string $url
	 * @return \Urlpin\Helper\AbstractElement
	 */
	public function setUrl($url) {
		self::$url = $url ? $url : self::$url;
		if( $this->checkIsImage(self::$url) ) {
			$this->linkIsImage = true;
		}
		return $this;
	}

	public function setDom() {
		if(!$this->linkIsImage) {
			$http = new \Core\Http\Curl();
			$http->setUseragent($this->request->getServer('HTTP_USER_AGENT')?$this->request->getServer('HTTP_USER_AGENT'):'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.9) Gecko/20071025 Firefox/2.0.0.9');
			$http->setReferrer(self::$url);
			$http->useCurl(function_exists('curl_init'));
			$http->setMaxredirect(5);
			$http->execute(self::$url);
			if($http->getError()) {
				$this->error = $http->getError();
			} else {
				$html = $http->getResult();
				$this->dom = new \Core\Dom\Query($html);
				$meta = $this->dom->query('meta');
				$charset = false;
				if($meta->count()) {
					for($i=0; $i<$meta->count(); $i++) {
						$content = $meta->getElement($i)->getAttribute('content');
						if(preg_match('/charset=([^\"\']+)/', $content, $match)) {
							$this->charset = trim($match[1]);
						}
					}
				}
			}	
		}
		
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getError() {
		return $this->error;
	}
	
	/**
	 * @param string $url
	 * @return boolean|array
	 */
	protected function checkIsImage($url) { 
		if($url) {
			$url = \Core\Url\Format::toAbsolute(self::$url, $url);
			if(isset($this->imagesInfo[$url])) {
				return $this->imagesInfo[$url];
			} else {
				$imageSizeObject = new \Core\Image\Getimagesize($url);
				$allowed = $this->getAllowedMime(); 
				if( is_array( $image_info = $imageSizeObject->getSize() ) ) {
					if( array_search($image_info['mime'], $allowed) ) {
						$this->imagesInfo[$url] = array(
							'width' => $image_info[0],
							'height' => $image_info[1],
							'title' => '',
							'mime' => $image_info['mime'],
							'image' => $url,
							'url' => self::$url,
							'price' => 0
						);
						return $this->imagesInfo[$url];
					}
				}
			}
		}
		return false;
	}
	
	
}