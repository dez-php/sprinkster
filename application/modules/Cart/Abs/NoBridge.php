<?php

namespace Cart\Abs;

class NoBridge extends Bridge {

	public function addAction($data = []) {}
	
	public function completeAction($data = []) {}
	
	public function removeAction($data = []) {}
	
	public function getTitle() { 
		return $this->getOrder() ? $this->getOrder()->getItems()->at(0)->name : null;
	}
	
	public function getInformation() {}

	public function getDueDate()
	{
		return NULL;
	}
	
	public function getProfileInformation() { 
		return false; 
	}
	
}