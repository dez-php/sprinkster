<?php

/**
 * @file    validators/number.php
 * @brief   number validator
 **/
namespace Core\Htmlform\Validators;

/**
 * @brief default validator for number input elements
 */
class Number extends \Core\Htmlform\Validators\Validator {
	// {{{ validate()
	/**
	 * @brief number validation
	 *
	 * @param $value (int)
	 *        	value to be validated
	 * @param $parameters (array)
	 *        	validation parameters
	 * @return (bool) validation result
	 *        
	 */
	public function validate($value, $parameters = array()) {
		$min = isset ( $parameters ['min'] ) ? $parameters ['min'] : null;
		$max = isset ( $parameters ['max'] ) ? $parameters ['max'] : null;
		
		return is_numeric ( $value ) && (($value >= $min) || ($min === null)) && (($value <= $max) || ($max === null));
	}
	// }}}
}
