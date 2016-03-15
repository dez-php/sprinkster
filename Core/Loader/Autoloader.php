<?php

namespace Core\Loader;

require_once 'Loader.php';
class Autoloader {
	/**
	 *
	 * @var \Core\Loader\Autoloader Singleton instance
	 */
	protected static $_instance;
	
	/**
	 *
	 * @var array Concrete autoloader callback implementations
	 */
	protected $_autoloaders = array ();
	
	/**
	 *
	 * @var array Default autoloader callback
	 */
	protected $_defaultAutoloader = array (
			'\Core\Loader\Loader',
			'loadClass' 
	);
	
	/**
	 *
	 * @var bool Whether or not to act as a fallback autoloader
	 */
	protected $_fallbackAutoloader = false;
	
	/**
	 *
	 * @var array Callback for internal autoloader implementation
	 */
	protected $_internalAutoloader;
	
	/**
	 *
	 * @var array Supported namespaces 'Core' by default.
	 */
	protected $_namespaces = array ();
	
	/**
	 *
	 * @var array Namespace-specific autoloaders
	 */
	protected $_namespaceAutoloaders = array ();
	
	/**
	 *
	 * @var bool Whether or not to suppress file not found warnings
	 */
	protected $_suppressNotFoundWarnings = false;
	
	/**
	 * @var array
	 */
	protected $_class_aliases = array();
	
	/**
	 * Retrieve singleton instance
	 *
	 * @return \Core\Loader\Autoloader
	 */
	public static function getInstance() { 
		if (null === self::$_instance) {
			self::$_instance = new self ();
		}
		return self::$_instance;
	}
	
	/**
	 * @param string $name
	 * @param string $class
	 * @return \Core\Loader\Autoloader
	 */
	public function setAlias($name, $class) {
		$this->_class_aliases[ $name ] = $class;
		return $this;
	}
	
	/**
	 * @param array $array
	 * @return \Core\Loader\Autoloader
	 */
	public function setAliases(array $array) {
		foreach($array AS $name => $class) {
			$this->setAlias($name, $class);
		}
		return $this;
	}
	
	/**
	 * @param string $name
	 * @return \Core\Loader\Autoloader
	 */
	public function removeAlias($name) {
		if(isset($this->_class_aliases[ $name ])) { 
			unset($this->_class_aliases[ $name ]); 
		}
		return $this;
	}
	
	/**
	 * @param string $name
	 * @return multitype:|NULL
	 */
	public function getAlias($name) {
		if($this->issetAlias($name)) { 
			return $this->_class_aliases[ $name ]; 
		}
		return null;
	}
	
	/**
	 * @return array
	 */
	public function getAliases() {
		return $this->_class_aliases;
	}
	
	/**
	 * @param string $name
	 * @return boolean
	 */
	public function issetAlias($name) {
		return array_key_exists($name, $this->_class_aliases);
	}
	
	/**
	 * Reset the singleton instance
	 *
	 * @return void
	 */
	public static function resetInstance() {
		self::$_instance = null;
	}
	
	/**
	 * Autoload a class
	 *
	 * @param string $class        	
	 * @return bool
	 */
	public static function autoload($class) {
		$self = self::getInstance ();
		foreach ( $self->getClassAutoloaders ( $class ) as $autoloader ) {
			if ($autoloader instanceof \Core\Loader\Autoloader) {
				if ($autoloader->autoload ( $class )) {
					return true;
				}
			} elseif (is_string ( $autoloader )) {
				if ($autoloader ( $class )) {
					return true;
				}
			} elseif (is_array ( $autoloader )) {
				$object = array_shift ( $autoloader );
				$method = array_shift ( $autoloader );
				if (call_user_func ( array (
						$object,
						$method 
				), $class )) {
					return true;
				}
			}
		}
		
		return false;
	}
	
	/**
	 * Set the default autoloader implementation
	 *
	 * @param string|array $callback
	 *        	PHP callback
	 * @return void
	 */
	public function setDefaultAutoloader($callback) {
		if (! is_callable ( $callback )) {
			throw new \Core\Exception ( 'Invalid callback specified for default autoloader' );
		}
		
		$this->_defaultAutoloader = $callback;
		return $this;
	}
	
	/**
	 * Retrieve the default autoloader callback
	 *
	 * @return string array Callback
	 */
	public function getDefaultAutoloader() {
		return $this->_defaultAutoloader;
	}
	
	/**
	 * Set several autoloader callbacks at once
	 *
	 * @param array $autoloaders
	 *        	Array of PHP callbacks (or \Core\Loader\Autoloader
	 *        	implementations) to act as autoloaders
	 * @return \Core\Loader\Autoloader
	 */
	public function setAutoloaders(array $autoloaders) {
		$this->_autoloaders = $autoloaders;
		return $this;
	}
	
	/**
	 * Get attached autoloader implementations
	 *
	 * @return array
	 */
	public function getAutoloaders() {
		return $this->_autoloaders;
	}
	
	/**
	 * Return all autoloaders for a given namespace
	 *
	 * @param string $namespace        	
	 * @return array
	 */
	public function getNamespaceAutoloaders($namespace) {
		$namespace = ( string ) $namespace;
		if (! array_key_exists ( $namespace, $this->_namespaceAutoloaders )) {
			return array ();
		}
		return $this->_namespaceAutoloaders [$namespace];
	}
	
	/**
	 * Register a namespace to autoload
	 *
	 * @param string|array $namespace        	
	 * @return \Core\Loader\Autoloader
	 */
	public function registerNamespace($namespace, $path = null) {
		if (is_array ( $namespace )) {
			return $this->registerNamespaces ( $namespace );
		} else if (! $path || is_array ( $path )) {
			throw new \Core\Exception ( 'Invalid namespace provided' );
		}
		$namespace = rtrim ( $namespace, '\\' ) . '\\';
		if (! isset ( $this->_namespaces [$namespace] )) {
			$this->_namespaces [$namespace] = $path;
		}
		return $this;
	}
	
	/**
	 *
	 * @param array $namespace        	
	 * @throws \Core\Exception
	 * @return \Core\Loader\Autoloader
	 */
	public function registerNamespaces($namespace) {
		if (! is_array ( $namespace )) {
			throw new \Core\Exception ( 'Invalid namespace provided' );
		}
		foreach ( $namespace as $ns => $path ) {
			$this->registerNamespace ( $ns, $path );
		}
		return $this;
	}
	
	/**
	 * Unload a registered autoload namespace
	 *
	 * @param string|array $namespace        	
	 * @return \Core\Loader\Autoloader
	 */
	public function unregisterNamespace($namespace) {
		if (is_string ( $namespace )) {
			$namespace = rtrim ( $namespace, '\\' ) . '\\';
			$namespace = ( array ) $namespace;
		} elseif (! is_array ( $namespace )) {
			throw new \Core\Exception ( 'Invalid namespace provided' );
		}
		
		foreach ( $namespace as $ns ) {
			if (isset ( $this->_namespaces [$ns] )) {
				unset ( $this->_namespaces [$ns] );
			}
		}
		return $this;
	}
	
	/**
	 * Get a list of registered autoload namespaces
	 *
	 * @return array
	 */
	public function getRegisteredNamespaces() {
		return $this->_namespaces;
	}
	
	/**
	 * Get or set the value of the "suppress not found warnings" flag
	 *
	 * @param null|bool $flag        	
	 * @return bool \Core\Loader\Autoloader boolean if no argument is passed,
	 *         object instance otherwise
	 */
	public function suppressNotFoundWarnings($flag = null) {
		if (null === $flag) {
			return $this->_suppressNotFoundWarnings;
		}
		$this->_suppressNotFoundWarnings = ( bool ) $flag;
		return $this;
	}
	
	/**
	 * Indicate whether or not this autoloader should be a fallback autoloader
	 *
	 * @param bool $flag        	
	 * @return \Core\Loader\Autoloader
	 */
	public function setFallbackAutoloader($flag) {
		$this->_fallbackAutoloader = ( bool ) $flag;
		return $this;
	}
	
	/**
	 * Is this instance acting as a fallback autoloader?
	 *
	 * @return bool
	 */
	public function isFallbackAutoloader() {
		return $this->_fallbackAutoloader;
	}
	
	/**
	 * Get autoloaders to use when matching class
	 *
	 * Determines if the class matches a registered namespace, and, if so,
	 * returns only the autoloaders for that namespace. Otherwise, it returns
	 * all non-namespaced autoloaders.
	 *
	 * @param string $class        	
	 * @return array Array of autoloaders to use
	 */
	public function getClassAutoloaders($class) {
		$namespace = false;
		$autoloaders = array ();
		
		// Add concrete namespaced autoloaders
		foreach ( array_keys ( $this->_namespaceAutoloaders ) as $ns ) {
			if ('' == $ns) {
				continue;
			}
			if (0 === strpos ( $class, $ns )) {
				$namespace = $ns;
				$autoloaders = $autoloaders + $this->getNamespaceAutoloaders ( $ns );
				break;
			}
		}
		
		// Add internal namespaced autoloader
		foreach ( $this->getRegisteredNamespaces () as $ns => $path ) {
			if (0 === strpos ( $class, $ns )) {
				$namespace = $ns;
				$autoloaders [] = $this->_internalAutoloader;
				break;
			}
		}
		
		// Add non-namespaced autoloaders
		$autoloaders = $autoloaders + $this->getNamespaceAutoloaders ( '' );
		
		// Add fallback autoloader
		if (! $namespace && $this->isFallbackAutoloader ()) {
			$autoloaders [] = $this->_internalAutoloader;
		}
		
		return $autoloaders;
	}
	
	/**
	 * Add an autoloader to the beginning of the stack
	 *
	 * @param object|array|string $callback
	 *        	PHP callback or \Core\Loader\Autoloader implementation
	 * @param string|array $namespace
	 *        	Specific namespace(s) under which to register callback
	 * @return \Core\Loader\Autoloader
	 */
	public function unshiftAutoloader($callback, $namespace = '') {
		$autoloaders = $this->getAutoloaders ();
		array_unshift ( $autoloaders, $callback );
		$this->setAutoloaders ( $autoloaders );
		
		$namespace = ( array ) $namespace;
		foreach ( $namespace as $ns ) {
			$autoloaders = $this->getNamespaceAutoloaders ( $ns );
			array_unshift ( $autoloaders, $callback );
			$this->_setNamespaceAutoloaders ( $autoloaders, $ns );
		}
		
		return $this;
	}
	
	/**
	 * Append an autoloader to the autoloader stack
	 *
	 * @param object|array|string $callback
	 *        	PHP callback or \Core\Loader\Autoloader implementation
	 * @param string|array $namespace
	 *        	Specific namespace(s) under which to register callback
	 * @return \Core\Loader\Autoloader
	 */
	public function pushAutoloader($callback, $namespace = '') {
		$autoloaders = $this->getAutoloaders ();
		array_push ( $autoloaders, $callback );
		$this->setAutoloaders ( $autoloaders );
		
		$namespace = ( array ) $namespace;
		foreach ( $namespace as $ns ) {
			$autoloaders = $this->getNamespaceAutoloaders ( $ns );
			array_push ( $autoloaders, $callback );
			$this->_setNamespaceAutoloaders ( $autoloaders, $ns );
		}
		
		return $this;
	}
	
	/**
	 * Remove an autoloader from the autoloader stack
	 *
	 * @param object|array|string $callback
	 *        	PHP callback or \Core\Loader\Autoloader implementation
	 * @param null|string|array $namespace
	 *        	Specific namespace(s) from which to remove autoloader
	 * @return \Core\Loader\Autoloader
	 */
	public function removeAutoloader($callback, $namespace = null) {
		if (null === $namespace) {
			$autoloaders = $this->getAutoloaders ();
			if (false !== ($index = array_search ( $callback, $autoloaders, true ))) {
				unset ( $autoloaders [$index] );
				$this->setAutoloaders ( $autoloaders );
			}
			
			foreach ( $this->_namespaceAutoloaders as $ns => $autoloaders ) {
				if (false !== ($index = array_search ( $callback, $autoloaders, true ))) {
					unset ( $autoloaders [$index] );
					$this->_setNamespaceAutoloaders ( $autoloaders, $ns );
				}
			}
		} else {
			$namespace = ( array ) $namespace;
			foreach ( $namespace as $ns ) {
				$autoloaders = $this->getNamespaceAutoloaders ( $ns );
				if (false !== ($index = array_search ( $callback, $autoloaders, true ))) {
					unset ( $autoloaders [$index] );
					$this->_setNamespaceAutoloaders ( $autoloaders, $ns );
				}
			}
		}
		
		return $this;
	}
	
	/**
	 * Constructor
	 *
	 * Registers instance with spl_autoload stack
	 *
	 * @return void
	 */
	protected function __construct() {
		$this->_namespaces ['Core\\'] = dirname ( __DIR__ ) . DIRECTORY_SEPARATOR;
		spl_autoload_register ( array (
				__CLASS__,
				'autoload' 
		) );
		$this->_internalAutoloader = array (
				$this,
				'_autoload' 
		);
	}
	
	/**
	 * Internal autoloader implementation
	 *
	 * @param string $class        	
	 * @return bool
	 */
	protected function _autoload($class) {
		$callback = $this->getDefaultAutoloader ();
		try {
			if ($this->suppressNotFoundWarnings ()) {
				@call_user_func ( $callback, $class );
			} else {
				call_user_func ( $callback, $class );
			}
			return $class;
		} catch ( \Core\Exception $e ) {
			return false;
		}
	}
	
	/**
	 * Set autoloaders for a specific namespace
	 *
	 * @param array $autoloaders        	
	 * @param string $namespace        	
	 * @return \Core\Loader\Autoloader
	 */
	protected function _setNamespaceAutoloaders(array $autoloaders, $namespace = '') {
		$namespace = ( string ) $namespace;
		$this->_namespaceAutoloaders [$namespace] = $autoloaders;
		return $this;
	}
	
	/**
	 *
	 * @param array $paths        	
	 * @return string
	 */
	public static function setIncludePaths($paths) {
		if (! is_array ( $paths )) {
			$paths = ( array ) $paths;
		}
		foreach ( $paths as $path ) {
			set_include_path ( implode ( PATH_SEPARATOR, array (
					realpath ( $path ),
					get_include_path () 
			) ) );
		}
		return true;
	}
}
 
