<?php

namespace Facebook;

class InvitedController extends \Base\PermissionController {
	
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
		
		$og = $this->getRequest()->getRequest('og_data');
		
		$invitedTable = new \Facebook\OauthFacebookInvite();
		if($this->getRequest()->isFacebookBot()) {
			$this->placeholder('meta_tags', $this->render('og_data', array('og_data' => $og), null, true));
				
			$this->render('index');
		} else if($invitedTable->countByCode($this->getRequest()->getRequest('invited_code'))) {
			$this->placeholder('meta_tags', $this->render('og_data', array('og_data' => $og), null, true));
			
			$this->render('index');
		} else {
			$this->forward('error404');
		}
	}
	
}