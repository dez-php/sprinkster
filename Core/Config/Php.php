<?php

namespace Core\Config;

class Php extends \Core\Config\Main {
	/**
	 * String that separates nesting levels of configuration data identifiers
	 *
	 * @var string
	 */
	protected $_nestSeparator = '.';
	
	/**
	 * String that separates the parent section name
	 *
	 * @var string
	 */
	protected $_sectionSeparator = ':';
	
	/**
	 * Whether to skip extends or not
	 *
	 * @var boolean
	 */
	protected $_skipExtends = false;
	
	/**
	 * Loads the section $section from the config file $filename for
	 * access facilitated by nested object properties.
	 *
	 * If the section name contains a ":" then the section name to the right
	 * is loaded and included into the properties. Note that the keys in
	 * this $section will override any keys of the same
	 * name in the sections that have been included via ":".
	 *
	 * If the $section is null, then all sections in the php file are loaded.
	 *
	 * If any key includes a ".", then this will act as a separator to
	 * create a sub-property.
	 *
	 * example php file:
	 * return array(
	 * "all" => array(
	 * "hostname" => "live"
	 * ),
	 * "staging : all" => array(
	 * "hostname" => "staging"
	 * )
	 * ),
	 *
	 * after calling $data = new \Core\Config\Php($file, 'staging'); then
	 * $data->hostname === "staging"
	 * $data->db->connection === "database"
	 *
	 * The $options parameter may be provided as either a boolean or an array.
	 * If provided as a boolean, this sets the $allowModifications option of
	 * \Core\Config. If provided as an array, there are two configuration
	 * directives that may be set. For example:
	 *
	 * $options = array(
	 * 'allowModifications' => false,
	 * 'nestSeparator' => '->'
	 * );
	 *
	 * @param string $filename        	
	 * @param string|null $section        	
	 * @param boolean|array $options        	
	 * @throws \Core\Exception
	 * @return void
	 */
	public function __construct($filename, $section = null, $options = false, $parse_key = false) {
		if (empty ( $filename )) {
			/**
			 *
			 * @see \Core\Exception
			 */
			throw new \Core\Exception ( 'Filename is not set' );
		}
		
		$allowModifications = false;
		if (is_bool ( $options )) {
			$allowModifications = $options;
		} elseif (is_array ( $options )) {
			if (isset ( $options ['allowModifications'] )) {
				$allowModifications = ( bool ) $options ['allowModifications'];
			}
			if (isset ( $options ['nestSeparator'] )) {
				$this->_nestSeparator = ( string ) $options ['nestSeparator'];
			}
			if (isset ( $options ['skipExtends'] )) {
				$this->_skipExtends = ( bool ) $options ['skipExtends'];
			}
		}
		
		$phpArray = $this->_loadPhpFile ( $filename );
		
		if (null === $section) {
			// Load entire file
			$dataArray = array ();
			foreach ( $phpArray as $sectionName => $sectionData ) {
				if (! is_array ( $sectionData )) {
					$dataArray = $this->_arrayMergeRecursive ( $dataArray, $this->_processKey ( array (), $sectionName, $sectionData ) );
				} else {
					if ($parse_key) {
						$dataArray = $this->_arrayMergeRecursive ( $dataArray, $this->_processKey ( array (), $sectionName, $sectionData ) );
					} else {
						$dataArray [$sectionName] = $this->_processSection ( $phpArray, $sectionName );
					}
				}
			}
			parent::__construct ( $dataArray, $allowModifications );
		} else {
			// Load one or more sections
			if (! is_array ( $section )) {
				$section = array (
						$section 
				);
			}
			$dataArray = array ();
			foreach ( $section as $sectionName ) {
				if (! isset ( $phpArray [$sectionName] )) {
					/**
					 *
					 * @see \Core\Exception
					 */
					throw new \Core\Exception ( "Section '$sectionName' cannot be found in $filename" );
				}
				if (isset ( $phpArray [$sectionName] [';extends'] ) && isset ( $phpArray [$phpArray [$sectionName] [';extends']] )) {
					$dataArray = $this->_arrayMergeRecursive ( $this->_arrayMergeRecursive ( $phpArray [$phpArray [$sectionName] [';extends']], $phpArray [$sectionName] ), $dataArray );
				}
				$dataArray = $this->_arrayMergeRecursive ( $this->_processSection ( $phpArray, $sectionName ), $dataArray );
			}
			parent::__construct ( $dataArray, $allowModifications );
		}
		
		$this->_loadedSection = $section;
	}
	
	/**
	 * Load the php file from disk using include().
	 * Use a private error
	 * handler to convert any loading errors into a \Core\Exception
	 *
	 * @param string $filename        	
	 * @throws \Core\Exception
	 * @return array
	 */
	protected function _parsePhpFile($filename) {
		set_error_handler ( array (
				$this,
				'_loadFileErrorHandler' 
		) );
		$phpArray = include $filename;
		restore_error_handler ();
		
		// Check if there was a error while loading file
		if ($this->_loadFileErrorStr !== null) {
			/**
			 *
			 * @see \Core\Exception
			 */
			throw new \Core\Exception ( $this->_loadFileErrorStr );
		}
		
		return $phpArray;
	}
	
	/**
	 * Load the php file and preprocess the section separator (':' in the
	 * section name (that is used for section extension) so that the resultant
	 * array has the correct section names and the extension information is
	 * stored in a sub-key called ';extends'.
	 * We use ';extends' as this can
	 * never be a valid key name in an PHP file that has been loaded using
	 * include().
	 *
	 * @param string $filename        	
	 * @throws \Core\Exception
	 * @return array
	 */
	protected function _loadPhpFile($filename) {
		$loaded = $this->_parsePhpFile ( $filename );
		$phpArray = array ();
		foreach ( $loaded as $key => $data ) {
			$pieces = explode ( $this->_sectionSeparator, $key );
			$thisSection = trim ( $pieces [0] );
			switch (count ( $pieces )) {
				case 1 :
					$phpArray [$thisSection] = $data;
					break;
				
				case 2 :
					$extendedSection = trim ( $pieces [1] );
					$phpArray [$thisSection] = \Core\Arrays::array_merge ( array (
							';extends' => $extendedSection 
					), $data );
					break;
				
				default :
					/**
					 *
					 * @see \Core\Exception
					 */
					throw new \Core\Exception ( "Section '$thisSection' may not extend multiple sections in $filename" );
			}
		}
		
		return $phpArray;
	}
	
	/**
	 * Process each element in the section and handle the ";extends" inheritance
	 * key.
	 * Passes control to _processKey() to handle the nest separator
	 * sub-property syntax that may be used within the key name.
	 *
	 * @param array $phpArray        	
	 * @param string $section        	
	 * @param array $config        	
	 * @throws \Core\Exception
	 * @return array
	 */
	protected function _processSection($phpArray, $section, $config = array()) {
		$thisSection = $phpArray [$section];
		
		foreach ( $thisSection as $key => $value ) {
			if (strtolower ( $key ) == ';extends') {
				if (isset ( $phpArray [$value] )) {
					$this->_assertValidExtend ( $section, $value );
					
					if (! $this->_skipExtends) {
						$config = $this->_processSection ( $phpArray, $value, $config );
					}
				} else {
					/**
					 *
					 * @see \Core\Exception
					 */
					throw new \Core\Exception ( "Parent section '$section' cannot be found" );
				}
			} else {
				$config = $this->_processKey ( $config, $key, $value );
			}
		}
		return $config;
	}
	
	/**
	 * Assign the key's value to the property list.
	 * Handles the
	 * nest separator for sub-properties.
	 *
	 * @param array $config        	
	 * @param string $key        	
	 * @param string $value        	
	 * @throws \Core\Exception
	 * @return array
	 */
	protected function _processKey($config, $key, $value) {
		if (strpos ( $key, $this->_nestSeparator ) !== false) {
			$pieces = explode ( $this->_nestSeparator, $key, 2 );
			if (strlen ( $pieces [0] ) && strlen ( $pieces [1] )) {
				if (! isset ( $config [$pieces [0]] )) {
					if ($pieces [0] === '0' && ! empty ( $config )) {
						// convert the current values in $config into an array
						$config = array (
								$pieces [0] => $config 
						);
					} else {
						$config [$pieces [0]] = array ();
					}
				} elseif (! is_array ( $config [$pieces [0]] )) {
					/**
					 *
					 * @see \Core\Exception
					 */
					throw new \Core\Exception ( "Cannot create sub-key for '{$pieces[0]}' as key already exists" );
				}
				$config [$pieces [0]] = $this->_processKey ( $config [$pieces [0]], $pieces [1], $value );
			} else {
				/**
				 *
				 * @see \Core\Exception
				 */
				throw new \Core\Exception ( "Invalid key '$key'" );
			}
		} else {
			$config [$key] = $value;
		}
		return $config;
	}
}
