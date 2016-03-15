<?php

namespace Core\Form\Elements;

/**
 * @default validator for email input elements
 */
class Bool extends \Core\Form\Elements\Validator {
	// {{{ validate()
	/**
	 * @email validation
	 *
	 * @return (bool) validation result
	 *        
	 */
	public function validate() {
		return ( bool ) \Core\Http\Request::getInstance()->issetRequest($this->parameters['name']);
	}
	// }}}
	
	/**
	 *
	 * @return (string)
	 */
	public function getErrorMessage() {
		return $this->error_text ? $this->error_text : $this->translate->_ ( 'Please check' );
	}
}
