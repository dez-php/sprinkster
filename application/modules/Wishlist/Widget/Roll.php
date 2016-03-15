<?php

namespace Wishlist\Widget;

class Roll extends Grid {
	
	protected $view = 'roll';
	
	public function count() {
		return (new \Wishlist\Wishlist())->countBy($this->makeFilter());
	}

}