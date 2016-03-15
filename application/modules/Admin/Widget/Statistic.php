<?php

namespace Admin\Widget;

class Statistic extends \Base\Widget\AbstractMenu {
	
	protected $parent_id = null;
	
	/* (non-PHPdoc)
	 * @see \Core\Base\Action::init()
	 */
	public function init() {
		$this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	/* (non-PHPdoc)
	 * @see \Core\Base\Widget::result()
	 */
	public function result() {
		
		$this->render('index');
	}
	
}