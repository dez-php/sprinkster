<?php

namespace Facebook;

class TimelineController extends \Base\PermissionController {
	
	/**
	 * @var \Facebook\Helper\Me
	 */
	private $facebook;
	
	public function init() {
		if(!\Base\Config::get('facebook_status')) {
			$this->redirect( $this->url(array(),'welcome_home') );
		}
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
		$this->facebook = new \Facebook\Helper\Me();
	}
	
	public function indexAction() {
		$request = $this->getRequest();
		$userInfo = \User\User::getUserData();
		$this->close_box = false;
		if($userInfo->id) {
			if($request->getRequest('scope')) {
				$this->facebook->scope = $request->getRequest('scope');
			}
				
			$userFbData = $this->facebook->getUser();

			if(isset($userFbData['id'])) {
				$permissions = $this->facebook->getPermisions('publish_actions');
				
				$userFbDataTable = new \Facebook\OauthFacebook();
				$userFbDataDb = $userFbDataTable->fetchRow(array('facebook_id = ?' => $userFbData['id']));
				
				if( $permissions ) {
					if($userFbDataDb) {
						if($userFbDataDb->timeline) {
							$userFbDataDb->timeline = 0;
						} else {
							$userFbDataDb->timeline = 1;
						}
						$userFbDataDb->save();
					}
					$this->close_box = true;
				} else {
					$redirect = $this->facebook->getLoginLink( $this->url(array(),'settings'), 'timeline', 'popup' );
					$this->redirect($redirect);
				}
		
			} else {
				$redirect = $this->facebook->getLoginLink( $this->url(array(),'settings'), 'timeline', 'popup' );
				$this->redirect($redirect);
			}
		} else {
			$this->close_box = true;
		}
		$this->render('index');
	}
	
}