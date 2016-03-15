<?php

namespace Base\Traits;

trait Util
{
	/**
	 * Performs a check if an object is inheriting class or implementing interface
	 * @param  string        $type Name of a class or interface
	 * @return boolean       True if descendant class or implenting interface, false otherwise
	 */
	public function is($type)
	{
		if(!$type)
			return FALSE;

		// Trim extra backslash of namespace (it will break the check)
		if(0 === strpos($type, '\\'))
			$type = substr($type, 1);

		// It is class so we check if this object iherits the class
		if(class_exists($type, FALSE))
			return $this instanceof $type;

		// It is interface and we have to check if the class implements it
		if(interface_exists($type, FALSE))
		{
			$ifaces = class_implements($this);

			if(is_array($ifaces) && in_array($type, $ifaces))
				return TRUE;
		}

		return FALSE;
	}

	public function isModuleAccessible($namespace)
	{
		$action = \Core\Base\Action::getInstance();

		$module = $action->getModule($action->getFrontController()->formatModuleName($namespace));

		if(!$module)
			return FALSE;

		return $module->isAccessible();
	}
	
	public static function getModuleBaseNamespace($children) {

		if(!$children || !is_string($children))
			return NULL;
		
		$parts = array_filter(preg_split ( '/[^a-z0-9]/i', $children ));
		return \Core\Base\Front::getInstance()->formatModuleName(array_shift($parts));
	}

	public static function isOf($object, $type)
	{
		if(!$type)
			return FALSE;

		// Trim extra backslash of namespace (it will break the check)
		if(0 === strpos($type, '\\'))
			$type = substr($type, 1);

		// It is class so we check if this object iherits the class
		if(class_exists($type, FALSE))
			return $object instanceof $type;

		// It is interface and we have to check if the class implements it
		if(interface_exists($type, FALSE))
		{
			$ifaces = class_implements($object);

			if(is_array($ifaces) && in_array($type, $ifaces))
				return TRUE;
		}

		return FALSE;
	}
	
}