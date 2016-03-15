<?php

namespace Core\Reflection\Docblock\Tag;

class ReturnTag extends \Core\Reflection\Docblock\Tag {
	/**
	 *
	 * @var string
	 */
	protected $_type = null;
	
	/**
	 * Constructor
	 *
	 * @param string $tagDocblockLine        	
	 * @return \\Core\Reflection\Docblock_Tag_Return
	 */
	public function __construct($tagDocblockLine) {
		if (! preg_match ( '#^@(\w+)\s+([^\s]+)(?:\s+(.*))?#', $tagDocblockLine, $matches )) {
			require_once 'Reflection/Exception.php';
			throw new \Core\Reflection\Exception ( 'Provided docblock line is does not contain a valid tag' );
		}
		
		if ($matches [1] != 'return') {
			require_once 'Reflection/Exception.php';
			throw new \Core\Reflection\Exception ( 'Provided docblock line is does not contain a valid @return tag' );
		}
		
		$this->_name = 'return';
		$this->_type = $matches [2];
		if (isset ( $matches [3] )) {
			$this->_description = $matches [3];
		}
	}
	
	/**
	 * Get return variable type
	 *
	 * @return string
	 */
	public function getType() {
		return $this->_type;
	}
}
