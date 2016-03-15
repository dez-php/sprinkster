<?php

namespace Core\Cache\Backend;

class Xcache extends \Core\Cache\Backend implements \Core\Cache\Backend\InterfaceBackend {
	
	/**
	 * Log message
	 */
	const TAGS_UNSUPPORTED_BY_CLEAN_OF_XCACHE_BACKEND = '\Core\Cache\Backend\Xcache::clean() : tags are unsupported by the Xcache backend';
	const TAGS_UNSUPPORTED_BY_SAVE_OF_XCACHE_BACKEND = '\Core\Cache\Backend\Xcache::save() : tags are unsupported by the Xcache backend';
	
	/**
	 * Available options
	 *
	 * =====> (string) user :
	 * xcache.admin.user (necessary for the clean() method)
	 *
	 * =====> (string) password :
	 * xcache.admin.pass (clear, not MD5) (necessary for the clean() method)
	 *
	 * @var array available options
	 */
	protected $_options = array (
			'user' => null,
			'password' => null 
	);
	
	/**
	 * Constructor
	 *
	 * @param array $options
	 *        	associative array of options
	 * @throws \Core\Cache\Exception
	 * @return void
	 */
	public function __construct(array $options = array()) {
		if (! extension_loaded ( 'xcache' )) {
			\Core\Cache\Base::throwException ( 'The xcache extension must be loaded for using this backend !' );
		}
		parent::__construct ( $options );
	}
	
	/**
	 * Test if a cache is available for the given id and (if yes) return it
	 * (false else)
	 *
	 * WARNING $doNotTestCacheValidity=true is unsupported by the Xcache backend
	 *
	 * @param string $id
	 *        	cache id
	 * @param boolean $doNotTestCacheValidity
	 *        	if set to true, the cache validity won't be tested
	 * @return string cached datas (or false)
	 */
	public function load($id, $doNotTestCacheValidity = false) {
		if ($doNotTestCacheValidity) {
			// $this->_log("\Core\Cache\Backend\Xcache::load() :
		// \$doNotTestCacheValidity=true is unsupported by the Xcache backend");
		}
		$tmp = xcache_get ( $id );
		if (is_array ( $tmp )) {
			return $tmp [0];
		}
		return false;
	}
	
	/**
	 * Test if a cache is available or not (for the given id)
	 *
	 * @param string $id
	 *        	cache id
	 * @return mixed false (a cache is not available) or "last modified"
	 *         timestamp (int) of the available cache record
	 */
	public function test($id) {
		if (xcache_isset ( $id )) {
			$tmp = xcache_get ( $id );
			if (is_array ( $tmp )) {
				return $tmp [1];
			}
		}
		return false;
	}
	
	/**
	 * Save some string datas into a cache record
	 *
	 * Note : $data is always "string" (serialization is done by the
	 * core not by the backend)
	 *
	 * @param string $data
	 *        	datas to cache
	 * @param string $id
	 *        	cache id
	 * @param array $tags
	 *        	array of strings, the cache record will be tagged by each
	 *        	string entry
	 * @param int $specificLifetime
	 *        	if != false, set a specific lifetime for this cache record
	 *        	(null => infinite lifetime)
	 * @return boolean true if no problem
	 */
	public function save($data, $id, $tags = array(), $specificLifetime = false) {
		$lifetime = $this->getLifetime ( $specificLifetime );
		$result = xcache_set ( $id, array (
				$data,
				time () 
		), $lifetime );
		if (count ( $tags ) > 0) {
			// $this->_log(self::TAGS_UNSUPPORTED_BY_SAVE_OF_XCACHE_BACKEND);
		}
		return $result;
	}
	
	/**
	 * Remove a cache record
	 *
	 * @param string $id
	 *        	cache id
	 * @return boolean true if no problem
	 */
	public function remove($id) {
		return xcache_unset ( $id );
	}
	
	/**
	 * Clean some cache records
	 *
	 * Available modes are :
	 * 'all' (default) => remove all cache entries ($tags is not used)
	 * 'old' => unsupported
	 * 'matchingTag' => unsupported
	 * 'notMatchingTag' => unsupported
	 * 'matchingAnyTag' => unsupported
	 *
	 * @param string $mode
	 *        	clean mode
	 * @param array $tags
	 *        	array of tags
	 * @throws \Core\Cache\Exception
	 * @return boolean true if no problem
	 */
	public function clean($mode = \Core\Cache\Base::CLEANING_MODE_ALL, $tags = array()) {
		switch ($mode) {
			case \Core\Cache\Base::CLEANING_MODE_ALL :
				// Necessary because xcache_clear_cache() need basic
				// authentification
				$backup = array ();
				if (isset ( $_SERVER ['PHP_AUTH_USER'] )) {
					$backup ['PHP_AUTH_USER'] = $_SERVER ['PHP_AUTH_USER'];
				}
				if (isset ( $_SERVER ['PHP_AUTH_PW'] )) {
					$backup ['PHP_AUTH_PW'] = $_SERVER ['PHP_AUTH_PW'];
				}
				if ($this->_options ['user']) {
					$_SERVER ['PHP_AUTH_USER'] = $this->_options ['user'];
				}
				if ($this->_options ['password']) {
					$_SERVER ['PHP_AUTH_PW'] = $this->_options ['password'];
				}
				
				$cnt = xcache_count ( XC_TYPE_VAR );
				for($i = 0; $i < $cnt; $i ++) {
					xcache_clear_cache ( XC_TYPE_VAR, $i );
				}
				
				if (isset ( $backup ['PHP_AUTH_USER'] )) {
					$_SERVER ['PHP_AUTH_USER'] = $backup ['PHP_AUTH_USER'];
					$_SERVER ['PHP_AUTH_PW'] = $backup ['PHP_AUTH_PW'];
				}
				return true;
				break;
			case \Core\Cache\Base::CLEANING_MODE_OLD :
				// $this->_log("\Core\Cache\Backend\Xcache::clean() :
				// CLEANING_MODE_OLD is unsupported by the Xcache backend");
				break;
			case \Core\Cache\Base::CLEANING_MODE_MATCHING_TAG :
			case \Core\Cache\Base::CLEANING_MODE_NOT_MATCHING_TAG :
			case \Core\Cache\Base::CLEANING_MODE_MATCHING_ANY_TAG :
				// $this->_log(self::TAGS_UNSUPPORTED_BY_CLEAN_OF_XCACHE_BACKEND);
				break;
			default :
				\Core\Cache\Base::throwException ( 'Invalid mode for clean() method' );
				break;
		}
	}
	
	/**
	 * Return true if the automatic cleaning is available for the backend
	 *
	 * @return boolean
	 */
	public function isAutomaticCleaningAvailable() {
		return false;
	}
}
