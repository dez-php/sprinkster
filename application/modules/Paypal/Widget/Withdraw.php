<?php
namespace Paypal\Widget;

use \Core\Base\Widget;

class Withdraw extends Widget {
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function result()
	{
		$this->render('index');
	}

}