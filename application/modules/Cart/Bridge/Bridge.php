<?php

namespace Cart\Bridge;

class Bridge extends \Core\Base\Core {

	protected $order;
	protected $callTo;
	
	public function __construct($cart, $callTo) {
		$this->order = $cart;
		$this->callTo = $callTo;
	}
	
	/**
	 * @return \Cart\Abs\Bridge
	 */
	public function getAdapter() {
		if(\Core\Loader\Loader::isLoadable($this->callTo) && $this->isModuleAccessible($this->getModuleBaseNamespace($this->callTo)) && class_exists($this->callTo)) {
			$bridge = new $this->callTo($this->order);
			if($bridge instanceof \Cart\Abs\Bridge)
				return $bridge;
		}
		return new \Cart\Abs\NoBridge($this->order);
	}
	
}