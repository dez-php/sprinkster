<?php

namespace Core\Cache\Frontend;

class Sector extends \Core\Cache\Core {
	/**
	 * This frontend specific options
	 *
	 * ====> (boolean) debug_header :
	 * - if true, a debug text is added before each cached pages
	 *
	 * ====> (boolean) content_type_memorization :
	 * - deprecated => use memorize_headers instead
	 * - if the Content-Type header is sent after the cache was started, the
	 * corresponding value can be memorized and replayed when the cache is hit
	 * (if false (default), the frontend doesn't take care of Content-Type
	 * header)
	 *
	 * ====> (array) memorize_headers :
	 * - an array of strings corresponding to some HTTP headers name. Listed
	 * headers
	 * will be stored with cache datas and "replayed" when the cache is hit
	 *
	 * ====> (array) default_options :
	 * - an associative array of default options :
	 * - (boolean) cache : cache is on by default if true
	 * - (boolean) cacheWithXXXVariables (XXXX = 'Get', 'Post', 'Session',
	 * 'Files' or 'Cookie') :
	 * if true, cache is still on even if there are some variables in this
	 * superglobal array
	 * if false, cache is off if there are some variables in this superglobal
	 * array
	 * - (boolean) makeIdWithXXXVariables (XXXX = 'Get', 'Post', 'Session',
	 * 'Files' or 'Cookie') :
	 * if true, we have to use the content of this superglobal array to make a
	 * cache id
	 * if false, the cache id won't be dependent of the content of this
	 * superglobal array
	 * - (int) specific_lifetime : cache specific lifetime
	 * (false => global lifetime is used, null => infinite lifetime,
	 * integer => this lifetime is used), this "lifetime" is probably only
	 * usefull when used with "regexps" array
	 * - (array) tags : array of tags (strings)
	 * - (int) priority : integer between 0 (very low priority) and 10 (maximum
	 * priority) used by
	 * some particular backends
	 *
	 * ====> (array) regexps :
	 * - an associative array to set options only for some REQUEST_URI
	 * - keys are (pcre) regexps
	 * - values are associative array with specific options to set if the regexp
	 * matchs on $_SERVER['REQUEST_URI']
	 * (see default_options for the list of available options)
	 * - if several regexps match the $_SERVER['REQUEST_URI'], only the last one
	 * will be used
	 *
	 * @var array options
	 */
	protected $_specificOptions = array (
			'debug_header' => false,
			'content_type_memorization' => false,
			'memorize_headers' => array (),
			'default_options' => array (
					'cache_with_get_variables' => false,
					'cache_with_post_variables' => false,
					'cache_with_session_variables' => false,
					'cache_with_files_variables' => false,
					'cache_with_cookie_variables' => false,
					'make_id_with_get_variables' => true,
					'make_id_with_post_variables' => true,
					'make_id_with_session_variables' => true,
					'make_id_with_files_variables' => true,
					'make_id_with_cookie_variables' => true,
					'cache' => true 
			) 
	);
	
	/**
	 *
	 * @var string
	 */
	protected $_lastIdCache = null;
	
	/**
	 *
	 * @var bool
	 */
	protected $_lastIdLoadFromCache = false;
	
	/**
	 * Internal array to store some options
	 *
	 * @var array associative array of options
	 */
	protected $_activeOptions = array ();
	
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
		while ( list ( $name, $value ) = each ( $options ) ) {
			$name = strtolower ( $name );
			switch ($name) {
				case 'default_options' :
					$this->_setDefaultOptions ( $value );
					break;
				default :
					$this->setOption ( $name, $value );
			}
		}
		$this->setOption ( 'automatic_serialization', false );
	}
	
	/**
	 * Specific setter for the 'default_options' option (with some additional
	 * tests)
	 *
	 * @param array $options
	 *        	Associative array
	 * @throws \Core\Cache\Exception
	 * @return void
	 */
	protected function _setDefaultOptions($options) {
		if (! is_array ( $options )) {
			\Core\Cache\Base::throwException ( 'default_options must be an array !' );
		}
		foreach ( $options as $key => $value ) {
			if (! is_string ( $key )) {
				\Core\Cache\Base::throwException ( "invalid option [$key] !" );
			}
			$key = strtolower ( $key );
			if (isset ( $this->_specificOptions ['default_options'] [$key] )) {
				$this->_specificOptions ['default_options'] [$key] = $value;
			}
		}
	}
	
	/**
	 * Start the cache
	 *
	 * @param string $id
	 *        	A cache id (if you set a value here, maybe you have to use
	 *        	Output frontend instead)
	 * @return boolean True if the cache is hit (false else)
	 */
	public function start($id = false) {
		$this->_activeOptions = $this->_specificOptions ['default_options'];
		$id = $this->_clearId ( $id );
		if (! $id) {
			$id = $this->_makeId ();
			if (! $id) {
				return false;
			}
		}
		$this->_lastIdCache = $id;
		
		$array = $this->load ( $id );
		if ($array !== false) {
			$data = $array;
			if ($this->_specificOptions ['debug_header']) {
				echo 'DEBUG HEADER : This is a cached page !';
			}
			$this->_lastIdLoadFromCache = true;
			echo $data;
			return true;
		}
		
		ob_start ();
		ob_implicit_flush ( false );
	}
	public function end($specificLifetime = false, $forcedDatas = null, $echoData = true, $priority = 8) {
		if ($this->_lastIdLoadFromCache) {
			return true;
		}
		
		if ($forcedDatas === null) {
			$data = ob_get_clean ();
		} else {
			$data = & $forcedDatas;
		}
		$id = $this->_lastIdCache;
		if ($id === null) {
			\Core\Cache\Base::throwException ( 'use of end() without a start()' );
		}
		$this->save ( $data, $id, array (), $specificLifetime, $priority );
		echo $data;
	}
	public function _clearId($id) {
		return preg_replace ( '~[^a-z0-9]~i', '_', $id );
	}
	
	/**
	 * Make an id depending on REQUEST_URI and superglobal arrays (depending on
	 * options)
	 *
	 * @return mixed false cache id (string), false if the cache should have not
	 *         to be used
	 */
	protected function _makeId() {
		$tmp = $_SERVER ['REQUEST_URI'];
		$array = explode ( '?', $tmp, 2 );
		$tmp = $array [0];
		foreach ( array (
				'Get',
				'Post',
				'Session',
				'Files',
				'Cookie' 
		) as $arrayName ) {
			$tmp2 = $this->_makePartialId ( $arrayName, $this->_activeOptions ['cache_with_' . strtolower ( $arrayName ) . '_variables'], $this->_activeOptions ['make_id_with_' . strtolower ( $arrayName ) . '_variables'] );
			if ($tmp2 === false) {
				return false;
			}
			$tmp = $tmp . $tmp2;
		}
		return md5 ( $tmp );
	}
	
	/**
	 * Make a partial id depending on options
	 *
	 * @param string $arrayName
	 *        	Superglobal array name
	 * @param bool $bool1
	 *        	If true, cache is still on even if there are some variables in
	 *        	the superglobal array
	 * @param bool $bool2
	 *        	If true, we have to use the content of the superglobal array
	 *        	to make a partial id
	 * @return mixed false id (string) or false if the cache should have not to
	 *         be used
	 */
	protected function _makePartialId($arrayName, $bool1, $bool2) {
		switch ($arrayName) {
			case 'Get' :
				$var = $_GET;
				break;
			case 'Post' :
				$var = $_POST;
				break;
			case 'Session' :
				if (isset ( $_SESSION )) {
					$var = $_SESSION;
				} else {
					$var = null;
				}
				break;
			case 'Cookie' :
				if (isset ( $_COOKIE )) {
					$var = $_COOKIE;
				} else {
					$var = null;
				}
				break;
			case 'Files' :
				$var = $_FILES;
				break;
			default :
				return false;
		}
		if ($bool1) {
			if ($bool2) {
				return serialize ( $var );
			}
			return '';
		}
		if (count ( $var ) > 0) {
			return false;
		}
		return '';
	}
}

?>