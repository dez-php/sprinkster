<?php

namespace User\Widget;

class Terms  extends \Core\Base\Widget {

	protected $pin;
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function result() {
		$request = $this->getRequest();
		$data = array();
		
		//// pages
		$data['page_term'] = (new \Page\Page())->getByKey('terms_register');
		$data['page_privacy'] = (new \Page\Page())->getByKey('privacy_register');
		//// end pages
		
		$this->render('index', $data);
	}
	
	
}