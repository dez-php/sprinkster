<?php

namespace Urlpin;

class IndexController extends \Base\PermissionController {
	
	public function init() {
		$request = $this->getRequest();
		if($request->isXmlHttpRequest()) {
			$this->noLayout(true);
		}
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() {	
		$request = $this->getRequest();
		$data = array(
			'location' => false
		);
		$userInfo = \User\User::getUserData();
		if(!$userInfo->id) {
			$data['location'] = $this->url(array('controller' => 'login'),'user_c');
		}
		
		$data['isXmlHttpRequest'] = $request->isXmlHttpRequest();
		
		$this->render('index', $data);
		
	}
	
	
	
}

?>