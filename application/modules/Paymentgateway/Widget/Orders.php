<?php

namespace Paymentgateway\Widget;

class Orders extends \Base\Widget\AbstractMenuPermissionWidget {
	
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
		$menu = (new \Base\Menu())->fetchRow(['guid = ?' => '6a121718-351e-eb45-06f5-dc040c79e87']);
		if($menu && count(\Base\Menu::getMenu($menu->group_id, $menu->id)) <= 1)
			return;
		$this->render('index');
	}
	
}