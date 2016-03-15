<?php

namespace Core\Form\Elements;

/**
 * @customizable validator for input elements
 */
class Closure extends \Core\Form\Elements\Validator {
	// {{{ variables
	/**
	 * @ function to call
	 */
	protected $func;
	// }}}
	
	// {{{ validate()
	/**
	 * @ validates value with a callable function/closure
	 *
	 * @return (bool) validation result
	 *        
	 */
	public function validate() {
		return call_user_func ( $this->func, $this->value, $this->parameters );
	}
	// }}}
	
	// {{{ setClosure()
	/**
	 * @brief sets the validators validator function
	 *
	 * @param $func (closure)
	 *        	function
	 * @return void
	 *
	 */
	public function setFunc($func) {
		$this->func = $func;
	}
	// }}}
	
	/**
	 *
	 * @return (string)
	 */
	public function getErrorMessage() {
		return $this->error_text ? $this->error_text : $this->translate->_ ( 'Please enter valid data' );
	}
}

