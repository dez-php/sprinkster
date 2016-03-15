<?php

namespace Base\Widget;

class AbstractMenu extends \Core\Base\Widget {

	protected $is_group = 0;
	
	public function result() {}
	
	/**
	 * @param number|bool $is_group
	 * @return \Base\AbstractMenu
	 */
	public function setIs_group($is_group) {
		$this->is_group = $is_group;
		return $this;
	}
	
}