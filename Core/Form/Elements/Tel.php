<?php

namespace Core\Form\Elements;

/**
 * @default validator for tel input elements
 */
class Tel extends \Core\Form\Elements\RegEx {
	// {{{ __construct()
	/**
	 * @tel valdiator class constructor
	 *
	 * Sets regular expression for telephone number validation.
	 *
	 * @param $log (object)
	 *        	error logger
	 * @return void
	 *
	 */
	public function __construct($log = null) {
		parent::__construct ( $log );
		
		$this->regEx = '/^[0-9\/\(\)\+\-\. ]*$/';
	}
	// }}}
	
	// {{{ getPatternAttribute()
	/**
	 * @ returns HTML5 pattern attribute
	 *
	 * Overrides parent to return empty string (tel input element doesn't
	 * require pattern attribute for HTML5 validation).
	 *
	 * @return (string) empty attribute string
	 *        
	 */
	public function getPatternAttribute() {
		return '';
	}
	// }}}
	
	/**
	 *
	 * @return (string)
	 */
	public function getErrorMessage() {
		return $this->translate->_ ( 'Please enter a valid telephone number' );
	}
}
