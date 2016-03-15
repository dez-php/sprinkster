<?php

namespace Urlpin\Helper\Element\Xxx;

class Xtube extends \Urlpin\Helper\AbstractElement {
	
	public static function getRegularExpresions() {
		$self = new self();
		return array(
// 			'https?://((.*).)?xvideos\.com/video([0-9]{1,12})' => $self,
			'https?://((.*).)?xtube\.com/?' => $self,
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
					
					if(preg_match('~https?://(?:www\.)?xtube.com/watch.php\?v=([^\&\#]*)~i', self::$url, $match)) { 
						if(preg_match('~var currentVideoUrl =(.*);~imsU', $this->dom->getDocument(), $match)) {
							if( $result = @json_decode($match[1], true) ) {
								$result = parse_url($result);
								
								unset($result['query']);
								$result['path'] = dirname($result['path']) . '/240X180/1.jpg';
								$result['image_url'] = ($result['scheme'] . '://i' . mt_rand(1,9) . '.cdn1.videothumbs.xtube.com' . $result['path']);
								if(isset($result['image_url']) && is_array( $imgInfo = $this->checkIsImage( $result['image_url'] ) ) ) {
									if($title) { $imgInfo['title'] = $title; }
									$return['images'][$imgInfo['image']] = $imgInfo;
								}
							}
						}
						
					} else {
						$grid = $this->dom->query('.Card--video');
						if( ($total = $grid->count()) > 0 ) {
							for($i=0; $i<$total; $i++) {
								$box = $grid->get($i);
								$image = $box->query('.Card__thumb');
								
								if($image->count() && is_array( $imgInfo = $this->checkIsImage( $image->getElement(0)->getAttribute('data-thumb-bg') ) ) ) {
									$description = $box->query('.More-handle');
									$imgInfo['url'] = \Core\Url\Format::toAbsolute(self::$url, $image->getElement(0)->getAttribute('href'));
									if($description->count()) {
										$d = new \Core\Dom\Query($description->getElement(0)->getAttribute('data-powertip'));
										$imgInfo['title'] = trim($d->query('p')->innerHtml());
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