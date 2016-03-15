<?php

namespace Core\Behavior;

interface BehaviorInterface {
	/**
	 * Attaches the behavior object to the component.
	 * @param CComponent $component the component that this behavior is to be attached to.
	 */
	public function attach($component);
	/**
	 * Detaches the behavior object from the component.
	 * @param CComponent $component the component that this behavior is to be detached from.
	*/
	public function detach($component);
	/**
	 * @return boolean whether this behavior is enabled
	*/
	public function getEnabled();
	/**
	 * @param boolean $value whether this behavior is enabled
	*/
	public function setEnabled($value);
}