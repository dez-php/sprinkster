<?php

namespace User\Widget;

class Roll extends Grid {
	
	protected $view = 'roll';

	public function count() {
		return (new \User\User())->countBy($this->makeFilter());
	}
	
}