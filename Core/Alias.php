<?php

namespace Core;

class Alias {

	private $_aliases=array(); // alias => path
	
	public function __construct() {
		$this->_aliases['System'] = rtrim(\Core\Base\Init::getFMBase(),'\\/');
 		$this->_aliases['Fmx'] = rtrim(\Core\Base\Init::getFMBase(),'\\/') . DIRECTORY_SEPARATOR . 'Fmx';
	}
	
	public function get($alias) {
		if(isset($this->_aliases[$alias])) {
			return $this->_aliases[$alias];
		} elseif(($pos=strpos($alias,'.'))!==false) {
			$rootAlias=substr($alias,0,$pos);
			if(isset($this->_aliases[$rootAlias])) {
				return $this->_aliases[$alias]=rtrim($this->_aliases[$rootAlias].DIRECTORY_SEPARATOR.str_replace('.',DIRECTORY_SEPARATOR,substr($alias,$pos+1)),'*'.DIRECTORY_SEPARATOR);
			}
		}
		return false;
	}
	
	public function set($alias,$path) {
		$this->_aliases[$alias]=rtrim($path,'\\/');
	}
	
	public function unsets($alias) {
		if(isset($this->_aliases[$alias])) {
			unset($this->_aliases[$alias]);
		}
	}
	
}