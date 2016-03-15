<?php

namespace Core\Registry;

class Component extends \Core\Registry {

	public static function create($config) { 
		if(is_string($config)) {
			$class=$type=$config;
			$config=array();
		} elseif(isset($config['class'])) {
			$type=$class=$config['class'];
			if(isset($config['name'])) { $type = $config['name']; unset($config['name']); }
			unset($config['class']);
		} else {
			throw new \Core\Exception('Object configuration must be an array containing a "class" element.');
		}
		
		if(($n=func_num_args())>1) {
			$args=func_get_args();
			unset($args[0]);
			$class=new \ReflectionClass($class);
			// Note: ReflectionClass::newInstanceArgs() is available for PHP 5.1.3+
			$object=$class->newInstanceArgs($args);
			//$object=call_user_func_array(array($class,'__construct'),$args);
		} else {
			$object=new $class;
		}
		
		foreach($config as $key=>$value) {
			$object->$key=$value;
		}
		
		$std = new \stdClass();
		$std->object = $object;
		$std->config = $config;
		
		$instance = self::getInstance();
		$instance->set('component-'.$type, $std);
		
		return $object;
	}
	
	public static function getComponent($id,$createIfNull=true)
	{
		if(self::isRegistered('component-'.$id)) {
			return self::get('component-'.$id)->object;
		} elseif(self::isRegistered('Config-' . $id) && $createIfNull) {
			$config=self::get('Config-' . $id);
			if(!isset($config['enabled']) || $config['enabled'])
			{
				unset($config['enabled']);
				$component=self::createComponent($config);
				if(is_callable(array($component,'init'))) {
					$component->init();
				}
				return $component;
			}
		}
	}
	
	public static function getComponents()
	{
		$components = self::getRegExp('^component-');
		$result = array();
		foreach($components AS $name => $component) {
			$result[str_replace('component-','',$name)] = $component->object;
		}
		return $result;
	}
	
}