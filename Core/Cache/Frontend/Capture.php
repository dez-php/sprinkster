<?php

namespace Core\Cache\Frontend;

class Capture extends \Core\Cache\Core {
	/**
	 * Page identifiers
	 * 
	 * @var array
	 */
	protected $_idStack = array ();
	
	/**
	 * Tags
	 * 
	 * @var array
	 */
	protected $_tags = array ();
	protected $_extension = null;
	
	/**
	 * Start the cache
	 *
	 * @param string $id
	 *        	Cache id
	 * @return mixed True if the cache is hit (false else) with $echoData=true
	 *         (default) ; string else (datas)
	 */
	public function start($id, array $tags, $extension = null) {
		$this->_tags = $tags;
		$this->_extension = $extension;
		ob_start ( array (
				$this,
				'_flush' 
		) );
		ob_implicit_flush ( false );
		$this->_idStack [] = $id;
		return false;
	}
	
	/**
	 * callback for output buffering
	 * (shouldn't really be called manually)
	 *
	 * @param string $data
	 *        	Buffered output
	 * @return string Data to send to browser
	 */
	public function _flush($data) {
		$id = array_pop ( $this->_idStack );
		if ($id === null) {
			\Core\Cache\Base::throwException ( 'use of _flush() without a start()' );
		}
		if ($this->_extension) {
			$this->save ( serialize ( array (
					$data,
					$this->_extension 
			) ), $id, $this->_tags );
		} else {
			$this->save ( $data, $id, $this->_tags );
		}
		return $data;
	}
}
