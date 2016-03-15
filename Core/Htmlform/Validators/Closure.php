<?php

/**
 * @file    validators/closure.php
 * @brief   closure validator
 **/
namespace Core\Htmlform\Validators;

/**
 * @brief customizable validator for input elements
 */
class Closure extends \Core\Htmlform\Validators\Validator {
	// {{{ variables
	/**
	 * @brief function to call
	 */
	protected $func;
	// }}}
	
	// {{{ validate()
	/**
	 * @brief validates value with a callable function/closure
	 *
	 * @param $value (string)
	 *        	value to be validated
	 * @param $parameters (array)
	 *        	validation parameters
	 * @return (bool) validation result
	 *        
	 */
	public function validate($value, $parameters = array()) {
		return call_user_func ( $this->func, $value, $parameters );
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
}

