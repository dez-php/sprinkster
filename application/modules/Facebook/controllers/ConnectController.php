<?php

namespace Facebook;

class ConnectController extends \Base\PermissionController {
	
	/**
	 * @var \Facebook\Helper\Me
	 */
	private $facebook;
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
		$this->facebook = new \Facebook\Helper\Me();
		if(!\Base\Config::get('facebook_status')) {
			$this->redirect( $this->url(array(),'welcome_home') );
		}
	}
	
	public function indexAction() {
		$request = $this->getRequest();
		$userInfo = \User\User::getUserData();
		$this->close_box = false;
		if($userInfo->id) {
			if($request->getRequest('scope')) {
				$this->facebook->scope = $request->getRequest('scope');
			}
			
			$userFbData = null;
			try {
				$userFbData = $this->facebook->getUser();
			} catch (\Core\Exception $e) {}
			
			if(isset($userFbData['id'])) {

				$userFbDataTable = new \Facebook\OauthFacebook();
				$userFbDataDb = $userFbDataTable->fetchRow(array('facebook_id = ?' => $userFbData['id']));
				$userFbDataDb2 = $userFbDataTable->fetchRow(array('user_id = ?' => $userInfo->id));
				if($userFbDataDb) {
					if($userFbDataDb->user_id == $userInfo->id) {
						$enable_action = true;
					} else {
						$enable_action = $this->_('There is another profile that is associated with your Facebook account');
					}
				} else {
					$enable_action = true;
				}
				
				if($enable_action === true) {
					if($userFbDataDb2) { 
						if($userFbDataDb2->delete()) {
							$this->close_box = true;
						} else {
							$this->close_box = true;
						}
					} else {
						$new = $userFbDataTable->fetchNew();
						$new->user_id = $userInfo->id;
						$new->facebook_id = $userFbData['id'];
						$new->access_token = $userFbData['accessToken'];
						$new->date_added = \Core\Date::getInstance(null,\Core\Date::SQL_FULL, true)->toString();
						$new->date_modified = $new->date_added;
						if($new->save()) {
							$this->close_box = true;
						} else {
							$this->close_box = true;
						}
					}
				} else {
					\Core\Session\Base::set('connect_error', $enable_action);
					$this->close_box = true;
				}
				
			} else {
				$redirect = $this->facebook->getLoginLink( $this->url(array(),'settings'), 'connect', 'popup' );
				$this->redirect($redirect);
			}
		} else {
			$this->close_box = true;
		}
		$this->render('index');
	}
	
}