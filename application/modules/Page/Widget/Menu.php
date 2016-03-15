<?php

namespace Page\Widget;

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

		// get colors for menu
		$colorMenuTable = new \Page\PageMenu();
		$pages = $colorMenuTable->fetchMenu();
		
		$this->render('menu', array('pages' => $pages));
	}
	
}