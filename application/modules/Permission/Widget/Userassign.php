<?php

namespace Permission\Widget;

class Userassign extends \Core\Base\Widget {

	protected $widget;
	protected $form;

	public function init()
	{
		$this->_ = new \Translate\Locale('Backend\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function result()
	{
		$this->render('index');		
	}

}