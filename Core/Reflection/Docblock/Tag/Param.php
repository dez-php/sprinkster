<?php

namespace Core\Reflection\Docblock\Tag;

class Param extends \Core\Reflection\Docblock\Tag {
	/**
	 *
	 * @var string
	 */
	protected $_type = null;
	
	/**
	 *
	 * @var string
	 */
	protected $_variableName = null;
	
	/**
	 * Constructor
	 *
	 * @param string $tagDocblockLine        	
	 */
	public function __construct($tagDocblockLine) {
		$matches = array ();
		
		if (! preg_match ( '#^@(\w+)\s+([^\s]+)(?:\s+(\$\S+))?(?:\s+(.*))?#s', $tagDocblockLine, $matches )) {
			require_once 'Reflection/Exception.php';
			throw new \Core\Reflection\Exception ( 'Provided docblock line is does not contain a valid tag' );
		}
		
		if ($matches [1] != 'param') {
			require_once 'Reflection/Exception.php';
			throw new \Core\Reflection\Exception ( 'Provided docblock line is does not contain a valid @param tag' );
		}
		
		$this->_name = 'param';
		$this->_type = $matches [2];
		
		if (isset ( $matches [3] )) {
			$this->_variableName = $matches [3];
		}
		
		if (isset ( $matches [4] )) {
			$this->_description = preg_replace ( '#\s+#', ' ', $matches [4] );
		}
	}
	
	/**
	 * Get parameter variable type
	 *
	 * @return string
	 */
	public function getType() {
		return $this->_type;
	}
	
	/**
	 * Get parameter name
	 *
	 * @return string
	 */
	public function getVariableName() {
		return $this->_variableName;
	}
}
