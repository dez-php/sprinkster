<?php

namespace Core\Reflection\Docblock;

class Tag implements \Reflector {
	/**
	 *
	 * @var array Array of Class names
	 */
	protected static $_tagClasses = array (
			'param' => '\Core\Reflection\Docblock\Tag\Param',
			'return' => '\Core\Reflection\Docblock\Tag\ReturnTag' 
	);
	
	/**
	 *
	 * @var string
	 */
	protected $_name = null;
	
	/**
	 *
	 * @var string
	 */
	protected $_description = null;
	
	/**
	 * Factory: Create the appropriate annotation tag object
	 *
	 * @param string $tagDocblockLine        	
	 * @return \Core\Reflection\Docblock_Tag
	 */
	public static function factory($tagDocblockLine) {
		$matches = array ();
		
		if (! preg_match ( '#^@(\w+)(\s|$)#', $tagDocblockLine, $matches )) {
			require_once 'Reflection/Exception.php';
			throw new \Core\Reflection\Exception ( 'No valid tag name found within provided docblock line.' );
		}
		
		$tagName = $matches [1];
		if (array_key_exists ( $tagName, self::$_tagClasses )) {
			$tagClass = self::$_tagClasses [$tagName];
			if (! class_exists ( $tagClass )) {
				\Core\Loader\Loader::loadClass ( $tagClass );
			}
			return new $tagClass ( $tagDocblockLine );
		}
		return new self ( $tagDocblockLine );
	}
	
	/**
	 * Export reflection
	 *
	 * Required by Reflector
	 *
	 * @todo What should this do?
	 * @return void
	 */
	public static function export() {
	}
	
	/**
	 * Serialize to string
	 *
	 * Required by Reflector
	 *
	 * @todo What should this do?
	 * @return string
	 */
	public function __toString() {
		$str = "Docblock Tag [ * @" . $this->_name . " ]" . PHP_EOL;
		
		return $str;
	}
	
	/**
	 * Constructor
	 *
	 * @param string $tagDocblockLine        	
	 * @return void
	 */
	public function __construct($tagDocblockLine) {
		$matches = array ();
		
		// find the line
		if (! preg_match ( '#^@(\w+)(?:\s+([^\s].*)|$)?#', $tagDocblockLine, $matches )) {
			require_once 'Reflection/Exception.php';
			throw new \Core\Reflection\Exception ( 'Provided docblock line does not contain a valid tag' );
		}
		
		$this->_name = $matches [1];
		if (isset ( $matches [2] ) && $matches [2]) {
			$this->_description = $matches [2];
		}
	}
	
	/**
	 * Get annotation tag name
	 *
	 * @return string
	 */
	public function getName() {
		return $this->_name;
	}
	
	/**
	 * Get annotation tag description
	 *
	 * @return string
	 */
	public function getDescription() {
		return $this->_description;
	}
}
