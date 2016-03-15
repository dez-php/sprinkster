<?php

namespace Invite\Widget;

class Inviteheader extends \Core\Base\Widget {
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function result() {
		$this->render('index');
	}
	
}