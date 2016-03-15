<?php

namespace Core\Form\Elements;

/**
 * @default validator for email input elements
 */
class Username extends \Core\Form\Elements\Validator {
	// {{{ validate()
	/**
	 * @username validation
	 *
	 * @return (bool) validation result
	 *        
	 */
	public function validate() {
		return ( bool ) preg_match ( "/^[a-z0-9\_\.]+$/i", $this->value );
	}
	// }}}
	
	/**
	 *
	 * @return (string)
	 */
	public function getErrorMessage() {
		return $this->translate->_ ( 'Username must contain only a-z 0-9 _ .' );
	}
}
