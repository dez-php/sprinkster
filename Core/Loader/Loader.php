<?php

namespace Core\Loader;

class Loader {

    static private $modufication_namespaces = [];

	/**
	 *
	 * @return \Core\Loader\Autoloader
	 */
	public static function autloader() {
		return \Core\Loader\Autoloader::getInstance ();
	}
	
	/**
	 *
	 * @param string $class        	
	 * @param string|null $dirs        	
	 */
	public static function loadClass($class, $dirs = null) {
		if (class_exists ( $class, false ) || interface_exists ( $class, false )) {
			return;
		}
		
		if ((null !== $dirs) && ! is_string ( $dirs ) && ! is_array ( $dirs )) {
			throw new \Exception ( 'Directory argument must be a string or an array' );
		}
		
		if( ( $alias = self::autloader()->getAlias($class) ) !== null ) {
			class_alias($alias, $class, false);
			$class = $alias;
		}

		$className = ltrim ( $class, '\\' );
		$file = '';
		$namespace = '';
		$lastNsPos = strripos ( $className, '\\' );
		if ($lastNsPos) {
			$namespace = substr ( $className, 0, $lastNsPos );
			$className = substr ( $className, $lastNsPos + 1 );
			$file = str_replace ( '\\', DIRECTORY_SEPARATOR, $namespace ) . DIRECTORY_SEPARATOR;
		}
		$file .= str_replace ( '_', DIRECTORY_SEPARATOR, $className ) . '.php';
		
		if (! empty ( $dirs )) {
			// use the autodiscovered path
			$dirPath = dirname ( $file );
			if (is_string ( $dirs )) {
				$dirs = explode ( PATH_SEPARATOR, $dirs );
			}
			foreach ( $dirs as $key => $dir ) {
				if ($dir == '.') {
					$dirs [$key] = $dirPath;
				} else {
					$dir = rtrim ( $dir, '\\/' );
					$dirs [$key] = $dir . DIRECTORY_SEPARATOR . $dirPath;
				}
			}
			$file = basename ( $file );
			self::loadFile ( $file, $dirs, true );
		} else {
			self::loadFile ( $file, null, true );
		}

		if (! class_exists ( $class, false ) && ! interface_exists ( $class, false ) && ! trait_exists( $class, false )) {
			throw new \Exception ( "File \"$file\" does not exist or \"$class\" class, interface or trait was not found in the file." );
		}
	}
	
	/**
	 *
	 * @param string $filename        	
	 * @param string|null $dirs        	
	 * @param bool $once        	
	 * @return string
	 */
	public static function loadFile($filename, $dirs = null, $once = false) {
		self::_securityCheck ( $filename );
		
		if ($dirs) {
			$fileforcheck = $dirs . $filename;
		} else {
			$fileforcheck = $filename;
		}

        $namespacesRegistered = self::autloader ()->getRegisteredNamespaces ();
        if(defined('MODIFICATION_PATH') && is_dir(MODIFICATION_PATH)) {
            $self = new self();
            $namespacesModificationsRegistered = array_map(function($path) use($self) {
                if(isset($self::$modufication_namespaces[$path])) {
                    return $self::$modufication_namespaces[$path];
                }
                $new_path = str_replace(rtrim(\Core\Base\Init::getBase(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR,rtrim(MODIFICATION_PATH, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR,$path);
                if(!is_dir($new_path))
                    $new_path = $path;
                return $self::$modufication_namespaces[$path] = $new_path;
            }, self::autloader ()->getRegisteredNamespaces ());
            $loaded = self::_loadFile($fileforcheck, $once, $namespacesModificationsRegistered);
            if($loaded)
                return true;
        }
		$loaded = self::_loadFile($fileforcheck, $once, $namespacesRegistered);
        if($loaded)
            return true;

		if (! self::isReadable ( $fileforcheck )) {
			throw new \Exception ( 'File ' . $filename . ' not exist' );
		}
		
		$incPath = false;
		if (! empty ( $dirs ) && (is_array ( $dirs ) || is_string ( $dirs ))) {
			if (is_array ( $dirs )) {
				$dirs = implode ( PATH_SEPARATOR, $dirs );
			}
			$incPath = get_include_path ();
			set_include_path ( $dirs . PATH_SEPARATOR . $incPath );
		}
		
		if ($once) {
			include_once $filename;
		} else {
			include $filename;
		}
		
		if ($incPath) {
			set_include_path ( $incPath );
		}
		
		return false;
	}

    private static function _loadFile($fileforcheck, $once, $namespacesRegistered = []) {
        // with full path
        if (file_exists ( $fileforcheck ) && is_file ( $fileforcheck )) {
            if ($once) {
                include_once $fileforcheck;
            } else {
                include $fileforcheck;
            }
            return true;
        }

        $fileforcheck = str_replace ( '/', '\\', $fileforcheck );
        if (preg_match ( '/(?P<namespace>.+\\\)?(?P<class>[^\\\]+$)/', $fileforcheck, $matches )) {
            $matches ['class'] = str_replace ( '\\', DIRECTORY_SEPARATOR, $matches ['class'] );
            if (isset ( $namespacesRegistered [$matches ['namespace']] )) {
                $path = str_replace ( '\\', DIRECTORY_SEPARATOR, $namespacesRegistered [$matches ['namespace']] );
                if (strpos ( $matches ['class'], 'Controller.php' ) !== false) {
                    $path .= DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR;
                } else {
                    if (file_exists ( $path . DIRECTORY_SEPARATOR . 'Model' . DIRECTORY_SEPARATOR . $matches ['class'] )) {
                        $path .= DIRECTORY_SEPARATOR . 'Model' . DIRECTORY_SEPARATOR;
                    }

                    if (file_exists ( $path . DIRECTORY_SEPARATOR . 'Interface' . DIRECTORY_SEPARATOR . $matches ['class'] )) {
                        $path .= DIRECTORY_SEPARATOR . 'Interface' . DIRECTORY_SEPARATOR;
                    }
                }

                if (file_exists ( $path . $matches ['class'] )) {
                    if ($once) {
                        include_once $path . $matches ['class'];
                    } else {
                        include $path . $matches ['class'];
                    }
                    return true;
                }
            } else {
                foreach ( $namespacesRegistered as $ns => $dirs ) {
                    $dirs = str_replace ( '\\', DIRECTORY_SEPARATOR, $dirs );
                    if (0 === strpos ( $matches ['namespace'], $ns )) {
                        $path = dirname ( $dirs ) . DIRECTORY_SEPARATOR . $fileforcheck;
                        $path = str_replace ( '\\', DIRECTORY_SEPARATOR, $path );
                        if (file_exists ( $path )) {
                            if ($once) {
                                include_once $path;
                            } else {
                                include $path;
                            }
                            return true;
                        }
                    }
                }
            }
        }
    }
	
	/**
	 *
	 * @param string $filename        	
	 */
	protected static function _securityCheck($filename) {
		if (preg_match ( '/[^a-z0-9\\/\\\\_.:-]/i', $filename )) {
			throw new \Exception ( 'Security check: Illegal character in filename' );
		}
	}
	
	/**
	 *
	 * @param string $path        	
	 * @return multitype:
	 */
	public static function explodeIncludePath($path = null) {
		if (null === $path) {
			$path = get_include_path ();
		}
		
		if (PATH_SEPARATOR == ':') {
			// On *nix systems, include_paths which include paths with a stream
			// schema cannot be safely explode'd, so we have to be a bit more
			// intelligent in the approach.
			$paths = preg_split ( '#:(?!//)#', $path );
		} else {
			$paths = explode ( PATH_SEPARATOR, $path );
		}
		return $paths;
	}
	
	/**
	 *
	 * @param string $filename        	
	 * @return string string string string
	 */
	public static function isReadable($filename)
	{
		$filename = str_replace('\\', DIRECTORY_SEPARATOR, $filename);

		if (is_readable($filename))
			return true;
		
		$include_path = self::explodeIncludePath();
        if(defined('MODULES_PATH'))
		    $include_path[] = MODULES_PATH;

		foreach ($include_path as $path)
		{
			if ($path == '.')
			{
				if (is_readable($filename))
					return true;

				continue;
			}

			$file = implode(DIRECTORY_SEPARATOR, [ $path, $filename ]);

			if (is_readable($file))
				return true;
		}

		return false;
	}

	public static function isLoadable($filename)
	{
		$filename = str_replace('\\', DIRECTORY_SEPARATOR, $filename) . '.php';

		if (is_readable($filename))
			return true;
		
		$include_path = self::explodeIncludePath();
        if(defined('MODULES_PATH'))
		    $include_path[] = MODULES_PATH;

		foreach ($include_path as $path)
		{
			if ($path == '.')
			{
				if (is_readable($filename))
					return true;

				continue;
			}

			$file = implode(DIRECTORY_SEPARATOR, [ $path, $filename ]);

			if (is_readable($file))
				return true;
		}

		return false;
	}

	public static function load($filename)
	{
		$filename = str_replace(NS, DIRECTORY_SEPARATOR, $filename) . '.php';

		return self::loadClass($filename) ?: self::loadFile($filename);
	}
	
	/**
	 *
	 * @param array $paths        	
	 * @return string
	 */
	public static function setIncludePaths(array $paths) {
		foreach ( $paths as $path ) {
			set_include_path ( implode ( PATH_SEPARATOR, array (
					realpath ( $path ),
					get_include_path () 
			) ) );
		}
		return true;
	}
}
 
