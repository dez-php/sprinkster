<?php

namespace Wishlist\Widget;

class Headbar extends \Base\Widget\PermissionWidget {
	
	public $wishlist;
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function result() {
		if(isset($this->wishlist->id) && $this->wishlist->id) {
			$this->render('index', array('wishlist' => $this->wishlist));
		}
	}

}