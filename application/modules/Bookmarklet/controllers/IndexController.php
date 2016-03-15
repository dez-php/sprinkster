<?php

namespace Bookmarklet;

class IndexController extends \Base\PermissionController {
	
	public function init() {
		$this->noLayout(true);
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() {			
		$this->getResponse()->addHeader('Content-Type: text/javascript');
		$this->config_image_minimum_size = (int)\Base\Config::get('config_image_minimum_size');
		if(!$this->config_image_minimum_size) { $this->config_image_minimum_size = 80; }
		$this->render('index');
	}
	
	
	
}