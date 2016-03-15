<?php

namespace Core\Form\Elements;

/**
 * @default validator for email input elements
 */
class CountryIso extends \Core\Form\Elements\Validator {
	// {{{ validate()
	/**
	 * @email validation
	 *
	 * @return (bool) validation result
	 *        
	 */
	public function validate() {
		return preg_match('#[A-Z]{2,3}#', $this->value);
	}
	// }}}
	
	/**
	 *
	 * @return (string)
	 */
	public function getErrorMessage() {
		return $this->translate->_('Please select valid country');
	}
}
