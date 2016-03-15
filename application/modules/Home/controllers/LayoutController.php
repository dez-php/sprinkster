<?php

namespace Home;

class LayoutController extends \Core\Base\Action  {
	
	protected $hide_brand = false;
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\Layout', self::getModule('Language')->getLanguageId());
		$this->noLayout(true);
	}
	
	public function headerpartAction() {

		if(\Core\Registry::isRegistered('module_powered_check') && \Core\Registry::get('module_powered_check') == 'false' && \Base\Config::get('config_hide_brand')) {
			$this->hide_brand = true;
		}
		
		$this->render('header_part');

		//set page metatags global
		if(!$this->placeholder('meta_tags')) {
			$this->placeholder('meta_tags', $this->render('header_metas', null, ['module'=>'home','controller'=>'index'], true));
		}
	}	
	
	public function headerpartshortAction() {

		$this->render('header_part_short');

		//set page metatags global
		if(!$this->placeholder('meta_tags')) {
			$this->placeholder('meta_tags', $this->render('header_metas', null, ['module'=>'home','controller'=>'index'], true));
		}
	}	
	
    public function footerpartAction() {	
    	
		$this->render('footer_part', array());
	}
	
	public function leftpartAction() {
		
		$this->render('left_part', array());
	}
	
	public function headermetasAction($site_info = array()) {
		
		$this->render('header_metas', array());
	}

    public function promo() {
        if( (strpos(\Core\Registry::get('system_server'),$this->getRequest()->getDomain()) !== false || $this->getApplication()->getEnvironment() == 'development' ) && is_file(BASE_PATH . '/promo.html') )
            include BASE_PATH . '/promo.html';
    }
	
}

?>