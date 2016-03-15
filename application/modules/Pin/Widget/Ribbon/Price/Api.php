<?php

namespace Pin\Widget\Ribbon;

use \Core\Interfaces\IPersistentWidget;
use \Core\Interfaces\ICacheableWidget;

class Price extends \Base\Widget\PermissionWidget implements IPersistentWidget, ICacheableWidget {

	protected $pin;
	protected $template;

	public function init() {
		$this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	/**
	 * @param \Core\Db\Table\Row\AbstractRow $pin
	 * @return \Pin\Widget\Comment
	 */
	public function setPin($pin) {
		$this->pin = $pin;
		return $this;
	}
	
	/**
	 * @return \Core\Db\Table\Row\AbstractRow
	 */
	public function getPin() {
		return $this->pin;
	}

	public function result()
	{
		if(!$this->template || !$this->pin || !isset($this->pin->id))
			return;

		$price = NULL;

		//if($this->isModuleAccessible('Store') && ($price = (new \Store\PinQuantity)->fetchRow([ 'pin_id = ?' => (int) $this->pin->id ], 'id ASC')))
		if($this->isModuleAccessible('Store') && ($price = \Store\PinQuantity::get($this->pin->id, FALSE)))
			$price = $price->displayPrice();

		if(!$price && $this->pin->price)
			$price = $this->pin->getPrice();

		if(!$price)
			return;

		$this->render($this->template, [ 'price' => $price ]);
	}

}
