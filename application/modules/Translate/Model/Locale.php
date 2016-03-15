<?php

namespace Translate;

use \Core\Base\MemcachedManager;

class Locale extends \Core\Locale\Translate {

	public function __construct($namespace, $language_id) {
		$parts = explode('\\', $namespace);
		$namespace = ucfirst(strtolower($parts[0]));
		if(isset($parts[1])) { $namespace .= '\\' . ucfirst(strtolower($parts[1])); }
		$this->getTranslate($namespace, $language_id);
	}
	
	public function getTranslate($namespace) {
		$this->_namespace = $namespace;
		if(!isset(self::$_data[$namespace])) {
			self::$_data[$namespace] = Translate::getByNamespace($namespace);
		}
		return $this;
	}
	
// 	public function getTranslate($namespace) {
// 		$this->_namespace = $namespace;
// 		if(!self::$_data) {
// 			$data = Translate::getAll();
// 			foreach($data AS $d)
// 				self::$_data[$d['namespace']][$d['message']] = $d['message_translate'];
// 		}
// 		return $this;
// 	}
	
	public function toString($message) {
		if(isset(self::$_data[$this->_namespace][$message])) {
			return self::$_data[$this->_namespace][$message];
		} else {
			//if(\Core\Registry::get('environment') != 'production') {
			if($message) {
				if(Translate::insert(array(
					'message' => $message,
					'namespace' => $this->_namespace
				))) {
					$cache_key = MemcachedManager::key(__CLASS__, __METHOD__, 'Translate\Translate_getByNamespace_' . $this->_namespace);
					MemcachedManager::remove($cache_key);
					self::$_data[$this->_namespace][$message] = $message;
				}
			}
			//}
			return $message;
		}
	}
}