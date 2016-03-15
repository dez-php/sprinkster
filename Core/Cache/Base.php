<?php

namespace Core\Cache;

abstract class Base {
	
	/**
	 * Standard frontends
	 *
	 * @var array
	 */
	public static $standardFrontends = array (
			'Core',
			'Output',
			'Class',
			'File',
			'Function',
			'Page' 
	);
	
	/**
	 * Standard backends
	 *
	 * @var array
	 */
	public static $standardBackends = array (
			'File',
			'Sqlite',
			'Memcached',
			'Libmemcached',
			'Apc',
			'Xcache',
			'TwoLevels',
			'WinCache' 
	);
	
	/**
	 * Standard backends which implement the ExtendedInterface
	 *
	 * @var array
	 */
	public static $standardExtendedBackends = array (
			'File',
			'Apc',
			'TwoLevels',
			'Memcached',
			'Libmemcached',
			'Sqlite',
			'WinCache' 
	);
	
	/**
	 * Only for backward compatibility (may be removed in next major release)
	 *
	 * @var array
	 * @deprecated
	 *
	 */
	public static $availableFrontends = array (
			'Core',
			'Output',
			'Class',
			'File',
			'Function',
			'Page' 
	);
	
	/**
	 * Only for backward compatibility (may be removed in next major release)
	 *
	 * @var array
	 * @deprecated
	 *
	 */
	public static $availableBackends = array (
			'File',
			'Sqlite',
			'Memcached',
			'Libmemcached',
			'Apc',
			'Xcache',
			'WinCache',
			'TwoLevels' 
	);
	
	/**
	 * Consts for clean() method
	 */
	const CLEANING_MODE_ALL = 'all';
	const CLEANING_MODE_OLD = 'old';
	const CLEANING_MODE_MATCHING_TAG = 'matchingTag';
	const CLEANING_MODE_NOT_MATCHING_TAG = 'notMatchingTag';
	const CLEANING_MODE_MATCHING_ANY_TAG = 'matchingAnyTag';
	
	/**
	 * Factory
	 *
	 * @param mixed $frontend
	 *        	frontend name (string) or \Core\Cache\Frontend_ object
	 * @param mixed $backend
	 *        	backend name (string) or \Core\Cache\Backend\ object
	 * @param array $frontendOptions
	 *        	associative array of options for the corresponding frontend
	 *        	constructor
	 * @param array $backendOptions
	 *        	associative array of options for the corresponding backend
	 *        	constructor
	 * @param boolean $customFrontendNaming
	 *        	if true, the frontend argument is used as a complete class
	 *        	name ; if false, the frontend argument is used as the end of
	 *        	"\Core\Cache\Frontend\[...]" class name
	 * @param boolean $customBackendNaming
	 *        	if true, the backend argument is used as a complete class name
	 *        	; if false, the backend argument is used as the end of
	 *        	"\Core\Cache\Backend\[...]" class name
	 * @param boolean $autoload
	 *        	if true, there will no require_once for backend and frontend
	 *        	(useful only for custom backends/frontends)
	 * @throws \Core\Cache\Exception
	 * @return \Core\Cache\Core \Core\Cache\Frontend
	 */
	public static function factory($frontend, $backend, $frontendOptions = array(), $backendOptions = array(), $customFrontendNaming = false, $customBackendNaming = false, $autoload = false) {
		if (is_string ( $backend )) {
			$backendObject = self::_makeBackend ( $backend, $backendOptions, $customBackendNaming, $autoload );
		} else {
			if ((is_object ( $backend )) && (in_array ( '\Core\Cache\Backend\InterfaceBackend', class_implements ( $backend ) ))) {
				$backendObject = $backend;
			} else {
				self::throwException ( 'backend must be a backend name (string) or an object which implements \Core\Cache\Backend\InterfaceBackend' );
			}
		}
		if (is_string ( $frontend )) {
			$frontendObject = self::_makeFrontend ( $frontend, $frontendOptions, $customFrontendNaming, $autoload );
		} else {
			if (is_object ( $frontend )) {
				$frontendObject = $frontend;
			} else {
				self::throwException ( 'frontend must be a frontend name (string) or an object' );
			}
		}
		$frontendObject->setBackend ( $backendObject );
		return $frontendObject;
	}
	
	/**
	 * Backend Constructor
	 *
	 * @param string $backend        	
	 * @param array $backendOptions        	
	 * @param boolean $customBackendNaming        	
	 * @param boolean $autoload        	
	 * @return \Core\Cache\Backend
	 */
	public static function _makeBackend($backend, $backendOptions, $customBackendNaming = false, $autoload = false) {
		if (! $customBackendNaming) {
			$backend = self::_normalizeName ( $backend );
		}
		if (in_array ( $backend, \Core\Cache\Base::$standardBackends )) {
			// we use a standard backend
			$backendClass = '\Core\Cache\Backend\\' . $backend;
			// security controls are explicit
			require_once dirname ( dirname ( __DIR__ ) ) . str_replace ( '\\', DIRECTORY_SEPARATOR, $backendClass ) . '.php';
		} else {
			// we use a custom backend
			if (! preg_match ( '~^[\w\\\\]+$~D', $backend )) {
				\Core\Cache\Base::throwException ( "Invalid backend name [$backend]" );
			}
			if (! $customBackendNaming) {
				// we use this boolean to avoid an API break
				$backendClass = '\Core\Cache\Backend\\' . $backend;
			} else {
				$backendClass = $backend;
			}
			if (! $autoload) {
				$file = dirname ( dirname ( __DIR__ ) ) . str_replace ( '\\', DIRECTORY_SEPARATOR, $backendClass ) . '.php';
				if (! (self::_isReadable ( $file ))) {
					self::throwException ( "file $file not found in include_path" );
				}
				require_once $file;
			}
		}
		return new $backendClass ( $backendOptions );
	}
	
	/**
	 * Frontend Constructor
	 *
	 * @param string $frontend        	
	 * @param array $frontendOptions        	
	 * @param boolean $customFrontendNaming        	
	 * @param boolean $autoload        	
	 * @return \Core\Cache\Core \Core\Cache\Frontend
	 */
	public static function _makeFrontend($frontend, $frontendOptions = array(), $customFrontendNaming = false, $autoload = false) {
		if (! $customFrontendNaming) {
			$frontend = self::_normalizeName ( $frontend );
		}
		if (in_array ( $frontend, self::$standardFrontends )) {
			// we use a standard frontend
			// For perfs reasons, with frontend == 'Core', we can interact with
			// the Core itself
			$frontendClass = '\Core\Cache\\' . ($frontend != 'Core' ? 'Frontend\\' : '') . $frontend;
			// security controls are explicit
			require_once dirname ( dirname ( __DIR__ ) ) . str_replace ( '\\', DIRECTORY_SEPARATOR, $frontendClass ) . '.php';
		} else {
			// we use a custom frontend
			if (! preg_match ( '~^[\w\\\\]+$~D', $frontend )) {
				\Core\Cache\Base::throwException ( "Invalid frontend name [$frontend]" );
			}
			if (! $customFrontendNaming) {
				// we use this boolean to avoid an API break
				$frontendClass = '\Core\Cache\Frontend\\' . $frontend;
			} else {
				$frontendClass = $frontend;
			}
			if (! $autoload) {
				$file = dirname ( dirname ( __DIR__ ) ) . str_replace ( '\\', DIRECTORY_SEPARATOR, $frontendClass ) . '.php';
				if (! (self::_isReadable ( $file ))) {
					self::throwException ( "file $file not found in include_path" );
				}
				require_once $file;
			}
		}
		return new $frontendClass ( $frontendOptions );
	}
	
	/**
	 * Throw an exception
	 *
	 * Note : for perf reasons, the "load" of Core/Cache/Exception is dynamic
	 * 
	 * @param string $msg
	 *        	Message for the exception
	 * @throws \Core\Cache\Exception
	 */
	public static function throwException($msg, Exception $e = null) {
		// For perfs reasons, we use this dynamic inclusion
		require_once 'Cache/Exception.php';
		throw new \Core\Cache\Exception ( $msg, 0, $e );
	}
	
	/**
	 * Normalize frontend and backend names to allow multiple words TitleCased
	 *
	 * @param string $name
	 *        	Name to normalize
	 * @return string
	 */
	protected static function _normalizeName($name) {
		$name = ucfirst ( strtolower ( $name ) );
		$name = str_replace ( array (
				'-',
				'_',
				'.' 
		), ' ', $name );
		$name = ucwords ( $name );
		$name = str_replace ( ' ', '', $name );
		
		return $name;
	}
	
	/**
	 * Returns TRUE if the $filename is readable, or FALSE otherwise.
	 * This function uses the PHP include_path, where PHP's is_readable()
	 * does not.
	 *
	 * Note : this method comes from \Core\Loader\Loader (see #ZF-2891 for
	 * details)
	 *
	 * @param string $filename        	
	 * @return boolean
	 */
	private static function _isReadable($filename) {
		if (! $fh = @fopen ( $filename, 'r', true )) {
			return false;
		}
		@fclose ( $fh );
		return true;
	}
}
