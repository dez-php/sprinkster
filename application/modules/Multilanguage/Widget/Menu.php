<?php

namespace Multilanguage\Widget;

class Menu extends \Base\Widget\AbstractMenuPermissionWidget {
	
	protected $columns = 3;
	
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

		// get languages for menu
		$data['languages'] = self::getModule('Language')->getLanguages();
		$data['language'] = self::getModule('Language')->getLanguage();
		$this->render($this->checkSystemView('menu'), $data);
	}
	
}