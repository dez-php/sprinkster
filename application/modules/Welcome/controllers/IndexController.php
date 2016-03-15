<?php

namespace Welcome;

class IndexController extends \Core\Base\Action {
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
		$this->noLayout(true);
	}
	
	public function indexAction()
	{
		$this->placeholder('meta_tags', $this->render('header_metas', null, null,true));
		$this->render('index');
	}
	
}