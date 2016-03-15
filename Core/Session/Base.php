<?php

namespace Core\Session;

class Base {
	
	/**
	 *
	 * @var Core\Session\Base
	 */
	private static $_instance;
	
	/**
	 *
	 * @var \Core\Session\AbstractSession
	 */
	private static $adapter;
	public static $data = array ();
	private static $namespace = 'FM';
	private static $options = array ();
	
	/**
	 * Private list of php's ini values for ext/session
	 * null values will default to the php.ini value, otherwise
	 * the value below will overwrite the default ini value, unless
	 * the user has set an option explicity with setOptions()
	 *
	 * @var array
	 */
	private static $_defaultOptions = array (
			'save_path' => null,
			'name' => null, /* this should be set to a unique value for each application */
        'save_handler' => null,
			// 'auto_start' => null, /* intentionally excluded (see manual) */
			'gc_probability' => null,
			'gc_divisor' => null,
			'gc_maxlifetime' => null,
			'serialize_handler' => null,
			'cookie_lifetime' => null,
			'cookie_path' => null,
			'cookie_domain' => null,
			'cookie_secure' => null,
			'cookie_httponly' => null,
			'use_cookies' => null,
			'use_only_cookies' => 'on',
			'referer_check' => null,
			'entropy_file' => null,
			'entropy_length' => null,
			'cache_limiter' => null,
			'cache_expire' => null,
			'use_trans_sid' => null,
			'bug_compat_42' => null,
			'bug_compat_warn' => null,
			'hash_function' => null,
			'hash_bits_per_character' => null 
	);
	
	/**
	 *
	 * @var bool
	 */
	private static $_defaultOptionsSet = false;
	
	/**
	 *
	 * @param array $options        	
	 * @return \Core\Session\Base
	 */
	public static function getInstance($options = array()) {
		if (self::$_instance == null) {
			self::$_instance = new self ( $options );
		}
		return self::$_instance;
	}

	public function __construct($options = array()) {
		$PHPSESSID = \Core\Http\Request::getInstance ()->getRequest ( 'PHPSESSID' );
		if (strlen ( $PHPSESSID ) >= 8) {
			$this->sid ( $PHPSESSID );
		}
		
		// $adapter = false;
		// if(isset($options['adapter'])) {
		// $adapter = $options['adapter'];
		// unset($options['adapter']);
		// }
		
		if (is_array ( $options )) {
			self::$options = $options;
			foreach ( $options as $name => $value ) {
				$method = 'set' . $name;
				if (method_exists ( $this, $method )) {
					$this->$method ( $value );
				}
			}
		}
		
		// if($adapter) {
		// $this->setAdapter($adapter);
		// }
		
		if(!headers_sent())
			@session_start();
		
		self::$data = & $_SESSION;
		
		if (! isset ( self::$data [self::$namespace] ) || ! is_array ( self::$data [self::$namespace] )) {
			self::$data [self::$namespace] = array ();
		}
	}
	
	/**
	 *
	 * @param string(32) $sid        	
	 * @return \Core\Session\Base string
	 */
	public static function sid($sid = null) {
		if ($sid && mb_strlen ( $sid, 'utf-8' ) >= 8 && preg_match ( "/^([\w]{1,})$/i", $sid )) {
			session_id ( $sid );
			return self::$_instance;
		} else {
			return session_id ();
		}
	}
	
	/**
	 *
	 * @param string $adapter        	
	 * @return \Core\Session\Base
	 */
	public function setAdapter($adapter) {
		$class_name = '\Core\Session\\' . ucfirst ( strtolower ( $adapter ) );
		self::$adapter = new $class_name ( $this );
		return self::$_instance;
	}
	
	/**
	 *
	 * @return \Core\Session\Abstract
	 */
	public function getAdapter() {
		if (self::$adapter == null) {
			self::setAdapter ( 'normal' );
		}
		
		return self::$adapter;
	}
	
	/**
	 *
	 * @return string
	 */
	public static function getNamespace() {
		return self::$namespace;
	}
	
	/**
	 *
	 * @return array
	 */
	public static function getSettingsOptions() {
		return self::$options;
	}
	
	/**
	 *
	 * @param string $key        	
	 * @param multitype $value        	
	 * @return \Core\Session\Base
	 */
	public function __set($key, $value) {
		self::$data [self::$namespace] [$key] = $value;
		return $this;
	}
	
	/**
	 *
	 * @param string $key        	
	 * @return multitype NULL
	 */
	public function __get($key) {
		return isset ( self::$data [self::$namespace] [$key] ) ? self::$data [self::$namespace] [$key] : null;
	}
	
	/**
	 *
	 * @param string $key        	
	 * @param multitype $value        	
	 * @return \Core\Session\Base
	 */
	public static function set($spec, $value = null) {
		// self::$data[self::$namespace][$key] = $value;
		if ((null === $value) && ! is_array ( $spec )) {
			if (isset ( self::$data [self::$namespace] [$spec] )) {
				unset ( self::$data [self::$namespace] [$spec] );
				return self::$_instance;
			}
		}
		if ((null === $value) && is_array ( $spec )) {
			foreach ( $spec as $key => $value ) {
				self::set ( $key, $value );
			}
			return self::$_instance;
		}
		self::$data [self::$namespace] [$spec] = $value;
		return self::$_instance;
	}
	
	/**
	 *
	 * @param string $key        	
	 * @return multitype NULL
	 */
	public static function get($key) {
		// return isset(self::$data[self::$namespace][$key]) ?
		// self::$data[self::$namespace][$key] : null;
		$array_keys = array ();
		if (preg_match ( '/^(.*)\-\>(.*)$/', $key, $match )) {
			return isset ( self::$data [self::$namespace] [$match [1]]->{$match [2]} ) ? self::$data [self::$namespace] [$match [1]]->{$match [2]} : null;
		} elseif (preg_match ( '/^([^\[]{1,})\[(.*)\]+$/', $key, $match )) {
			$array_keys [] = $match [1];
			$ns = explode ( '[', '[' . $match [2] . ']' );
			foreach ( $ns as $nss ) {
				if ($nss) {
					$array_keys [] = trim ( $nss, '][' );
				}
			}
			
			$buf = self::$data [self::$namespace];
			
			foreach ( $array_keys as $k ) {
				if (isset ( $buf [$k] )) {
					$buf = $buf [$k];
				} else {
					$buf = null;
				}
			}
			return $buf;
		} else {
			return isset ( self::$data [self::$namespace] [$key] ) ? self::$data [self::$namespace] [$key] : null;
		}
	}
	public static function issetKey($key) {
		return isset ( self::$data [self::$namespace] [$key] );
	}
	
	/**
	 *
	 * @return multitype:
	 */
	public static function getAll() {
		return isset ( self::$data [self::$namespace] ) ? self::$data [self::$namespace] : null;
	}
	public static function clear($key = null) {
		if ($key === null) {
			self::$data [self::$namespace] = array ();
		} else {
			if (isset ( self::$data [self::$namespace] [$key] )) {
				unset ( self::$data [self::$namespace] [$key] );
			}
		}
		return self::$_instance;
	}
	
	/**
	 *
	 * @param array $options        	
	 * @return \Core\Session\Abstract
	 */
	public function setOptions($options) {
		foreach ( $options as $name => $value ) {
			$method = 'set' . $name;
			if (method_exists ( $this, $method )) {
				$this->$method ( $value );
			}
		}
		return $this;
	}
	
	/**
	 *
	 * @param string $value        	
	 * @return \Core\Session\Abstract
	 */
	public function setNamespace($value) {
		self::$namespace = $value;
		return self::$_instance;
	}
	
	/**
	 * setParams - set both the class specified
	 *
	 * @param array $userOptions
	 *        	- pass-by-keyword style array of <option name, option value>
	 *        	pairs
	 * @throws \Core\Session\Base
	 * @return void
	 */
	public static function setParams(array $userOptions = array()) {
		// set default options on first run only (before applying user settings)
		if (! self::$_defaultOptionsSet) {
			foreach ( self::$_defaultOptions as $defaultOptionName => $defaultOptionValue ) {
				if (isset ( self::$_defaultOptions [$defaultOptionName] )) {
					ini_set ( "session.$defaultOptionName", $defaultOptionValue );
				}
			}
			
			self::$_defaultOptionsSet = true;
		}
		
		// set the options the user has requested to set
		foreach ( $userOptions as $userOptionName => $userOptionValue ) {
			
			$userOptionName = strtolower ( $userOptionName );
			
			// set the ini based values
			if (array_key_exists ( $userOptionName, self::$_defaultOptions )) {
				ini_set ( "session.$userOptionName", $userOptionValue );
			} else {
				/**
				 * @see \Core\Exception
				 */
				require_once 'Exception.php';
				throw new \Core\Exception ( "Unknown option: $userOptionName = $userOptionValue" );
			}
		}
	}
	
	/**
	 * getOptions()
	 *
	 * @param string $optionName
	 *        	OPTIONAL
	 * @return array string
	 */
	public static function getOptions($optionName = null) {
		$options = array ();
		foreach ( ini_get_all ( 'session' ) as $sysOptionName => $sysOptionValues ) {
			$options [substr ( $sysOptionName, 8 )] = $sysOptionValues ['local_value'];
		}
		
		if ($optionName) {
			if (array_key_exists ( $optionName, $options )) {
				return $options [$optionName];
			}
			return null;
		}
		
		return $options;
	}

	public static function flash($key, $value = NULL)
	{
		if(2 > func_num_args())
			return self::get('flashdata_' . $key);

		self::set('flashdata_' . $key, $value);
	}
}

function clear_session_flashdata()
{
	foreach(\Core\Session\Base::$data as $key => $value)
		if(0 === strpos($key, 'flashdata_'))
			\Core\Session\Base::clear($key);
}

register_shutdown_function('\Core\Session\clear_session_flashdata');

?>