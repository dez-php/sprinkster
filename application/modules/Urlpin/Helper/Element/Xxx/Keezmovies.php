<?php

namespace Urlpin\Helper\Element\Xxx;

class Keezmovies extends \Urlpin\Helper\AbstractElement {
	
	public static function getRegularExpresions() {
		$self = new self();
		return array(
			'https?://((.*).)?keezmovies\.com/?' => $self,
		);
	}
	
	private function replace($src) {
		return preg_replace_callback('~(https?://((.*).).keezmovies.phncdn.com/)([^/]*)/([^/]*)([^=]*)$~',function($m) {
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
					
					if(preg_match('~https?://(?:www\.)?keezmovies\.com/video/(.*)/?~i', self::$url, $match)) {
						$html = @file_get_contents( str_replace('/video/', '/embed/', $match[0]) );
						if($html) {
							if(preg_match('~poster\s+:\s+[\'\"]?([^\'\"\?]*)~', $html, $m)) {
								if(is_array( $imgInfo = $this->checkIsImage( $m[1] ) ) ) {
									if($title) { $imgInfo['title'] = $title; }
									$return['images'][$imgInfo['image']] = $imgInfo;
								}
							}
						}
						
					} else {
						$grid = $this->dom->query('ul.ul_video_block li a.video_a');
						
						if( ($total = $grid->count()) > 0 ) {
							for($i=0; $i<$total; $i++) {
								$box = $grid->get($i);
								$image = $box->query('img');
								
								if($image->count() && is_array( $imgInfo = $this->checkIsImage( $this->replace($image->getElement(0)->getAttribute('src')) ) ) ) {
									$imgInfo['title'] = trim($grid->getElement($i)->getAttribute('title'));
									$imgInfo['url'] = \Core\Url\Format::toAbsolute(self::$url, $grid->getElement($i)->getAttribute('href'));
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