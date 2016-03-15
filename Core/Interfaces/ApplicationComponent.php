<?php

namespace Core\Interfaces;

interface ApplicationComponent {

	public function init();
	public function getIsInitialized();
	
}