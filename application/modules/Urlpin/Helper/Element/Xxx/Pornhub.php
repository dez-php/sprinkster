<?php

namespace Urlpin\Helper\Element\Xxx;

class Pornhub extends \Urlpin\Helper\AbstractElement {
	
	public static function getRegularExpresions() {
		$self = new self();
		return array(
			'http://(?:www\.)?pornhub\.com/?' => $self,
		);
	}
	
	private function replace($src) {
		return preg_replace_callback('~https?://((.*).).phncdn.com/videos/(.*)/([\d]{1,})x([\d]{1,})/(.*)$~',function($m) {
			return 'http://'.$m[1].'.phncdn.com/videos/' . $m[3] . '/400x300/' . $m[6];
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
					
					if(preg_match('~https?://(?:www\.)?pornhub.com/view_video.php\?viewkey\=([0-9]{1,12})~i', self::$url, $match)) {
						if(preg_match('~var flashvars =(.*);~imsU', $this->dom->getDocument(), $match)) {
							if( is_array( $result = json_decode($match[1], true) ) ) {
								$result = array_map('urldecode', $result);
								if(isset($result['image_url']) && is_array( $imgInfo = $this->checkIsImage( $result['image_url'] ) ) ) {
									if(isset($result['video_title'])) {
										$imgInfo['title'] = $result['video_title'];
									} else {
										if($title) { $imgInfo['title'] = $title; }
									}
									$return['images'][$imgInfo['image']] = $imgInfo;
								}
							}
						}
						
					} else {
						$grid = $this->dom->query('ul.videos li');
						
						if( ($total = $grid->count()) > 0 ) {
							for($i=0; $i<$total; $i++) {
								$box = $grid->get($i);
								$image = $box->query('.img img');
								if($image->count() && is_array( $imgInfo = $this->checkIsImage( $this->replace($image->getElement(0)->getAttribute('data-mediumthumb')) ) ) ) {
									$description = $box->query('.img');
									if($description->count()) {
										$imgInfo['title'] = trim($description->getElement(0)->getAttribute('data-title'));
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