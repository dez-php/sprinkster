<?php

namespace Urlpin\Helper;

class Element {
	
	/**
	 * @var \Urlpin\Helper\AbstractElement
	 */
	private $adapter;
	
	public $match = false;
	
	/**
	 * @param string $url
	 * @return \Urlpin\Helper\AbstractElement
	 */
	public function __construct($url) {
		$this->adapter = new \Urlpin\Helper\AbstractElement();
		$Directory = new \RecursiveDirectoryIterator(__DIR__ . DIRECTORY_SEPARATOR . 'Element' . DIRECTORY_SEPARATOR);
		$Iterator = new \RecursiveIteratorIterator($Directory);
		$adapters  = new \RegexIterator($Iterator, '/^.+\.php$/i');

		foreach($adapters AS $adapter) {
			if( preg_match('~(\\\Urlpin\\\Helper\\\Element\\\(.*)).php$~i', str_replace(DIRECTORY_SEPARATOR, '\\', $adapter), $match) ) {
				if(strpos($match[1],'Abstract')===false) {
					require_once $adapter;
					$className = $match[1];
					$object = new $className();
					if( $object instanceof \Urlpin\Helper\AbstractElement ) {
						$reg = $object->getRegularExpresions();
						if($reg && is_array($reg)) {
							foreach($reg AS $expresion => $object) {
								if(preg_match('~' . $expresion . '~i', $url)) {
									$object->setUrl($url);
									$this->adapter = $object;
									$this->match = true;
									break;
								}
							}
						}
					}
				}
			}
		} 
		if(!$this->match) {
			$this->adapter->setUrl($url);
		}
		return $this->adapter;
	}
	
	/**
	 * @return Ambigous <\Urlpin\Helper\AbstractElement, multitype:>|boolean
	 */
	public function getAdapter() {
		return $this->adapter;
	}
	
}