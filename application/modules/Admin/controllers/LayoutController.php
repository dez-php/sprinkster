<?php

namespace Admin;

class LayoutController extends \Core\Base\Action  {	
	
	public function init() {
		if(!\User\User::getUserData()->is_admin) { $this->redirect($this->url([],'admin_login')); }
		$this->_ = new \Translate\Locale('Backend\Layout', self::getModule('Language')->getLanguageId());
	}
	
	public function headerpartAction() {
		
		$data = array(
			'has_update' => self::getModule('Admin')->versionChecker()		
		);
		
		$this->render('header_part', $data);
	}	
	
    public function footerpartAction() {
    	$update = new \Update\Update();
    	$update->autoupdate();
    	
//     	\Core\Http\Thread::run(array(new \Update\Update(),'autoupdate'));
		$this->render('footer_part', array());
	}
	
}

?>