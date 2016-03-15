<?php

namespace Local\Traits;

use \Base\Traits\WysiwygFilter;

trait Allow {
	
	use WysiwygFilter;
	
	/**
	 * @var null|array
	 */
	public $errors;
	
	protected function getAllowedTypes() {
		$allowedUploadMimes = \Core\Registry::get('allowedmimeimages');
		if(is_array($allowedUploadMimes)) {
			$tmp = array();
			foreach($allowedUploadMimes AS $mime) {
				$part = explode('/', $mime);
				$tmp[$mime] = '.' . $part[1];
			}
			return $tmp;
		}
		return array();
	}
	
	protected function getAllowedTypesWithoutPoint() {
		$allowedUploadMimes = \Core\Registry::get('allowedmimeimages');
		if(is_array($allowedUploadMimes)) {
			return array_keys($allowedUploadMimes);
		}
		return array();
	}
	
	protected function getAllowedMime() {
		static $allowedUploadMimes = null;
		if($allowedUploadMimes === null)
			$allowedUploadMimes = \Core\Registry::get('allowedmimeimages');
		if(is_array($allowedUploadMimes))
			return $allowedUploadMimes;
		return ($allowedUploadMimes = array());
	}
	
	protected function isAllowSize($width, $height) {
		static $config_image_minimum_size = null;
		if($config_image_minimum_size === null) {
			$config_image_minimum_size = (int)\Base\Config::get('config_image_minimum_size');
			if(!$config_image_minimum_size) { $config_image_minimum_size = 80; }
		}
	
		return min($width, $height) >= $config_image_minimum_size;
	}
	
}