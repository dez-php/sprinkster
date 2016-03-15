<?php

namespace Core\Form\Elements;

/**
 * @default validator for url input elements
 */
class Url extends \Core\Form\Elements\Validator {
	
	/**
	 *
	 * @var null string
	 */
	protected $error_text = null;
	
	// {{{ validate()
	/**
	 * @ url validator
	 *
	 * @return (bool) validation result
	 *        
	 */
	public function validate() {
		return ( bool ) \Core\Form\Validator::clearHost($this->value);
// 		return ( bool ) filter_var ( $this->value, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED + FILTER_FLAG_HOST_REQUIRED );
	}
	// }}}
	
	/**
	 *
	 * @return (string)
	 */
	public function getErrorMessage() {
		return $this->error_text ? $this->error_text : $this->translate->_ ( 'Please enter a valid URL' );
	}
}