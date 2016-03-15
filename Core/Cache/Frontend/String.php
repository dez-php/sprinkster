<?php

namespace Core\Cache\Frontend;

class String extends \Core\Cache\Core {
	
	/**
	 * Constructor
	 *
	 * @param array $options
	 *        	Associative array of options
	 * @param boolean $doNotTestCacheValidity
	 *        	If set to true, the cache validity won't be tested
	 * @throws \Core\Cache\Exception
	 * @return void
	 */
	public function __construct(array $options = array()) {
		parent::__construct ( $options );
		$this->setOption ( 'automatic_serialization', true );
	}
	
	/**
	 * Start the cache
	 *
	 * @param string $id
	 *        	A cache id (if you set a value here, maybe you have to use
	 *        	Output frontend instead)
	 * @return boolean True if the cache is hit (false else)
	 */
	public function get($id) {
		$id = $this->_clearId ( $id );
		if (! $id) {
			return false;
		}
		$array = $this->load ( $id );
		if ($array !== false) {
			return $array;
		}
		return false;
	}
	public function set($id, $data, $specificLifetime = false, $priority = 8) {
		$id = $this->_clearId ( $id );
		if (! $id) {
			return false;
		}
		$this->save ( $data, $id, array (), $specificLifetime, $priority );
	}
	public function _clearId($id) {
		return preg_replace ( '~[^a-z0-9]~i', '_', $id );
	}
}

?>