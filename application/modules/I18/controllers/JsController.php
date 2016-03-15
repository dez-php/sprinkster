<?php

namespace I18;

class JsController extends \Base\PermissionController {
	public function indexAction() {
		$this->noLayout(true);
		$this->getResponse()->addHeader ( 'Content-type: application/javascript' );
		$this->render('index');	
	}
}