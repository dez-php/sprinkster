<?php

/**
 * @file    validators/email.php
 * @brief   email validator
 **/
namespace Core\Htmlform\Validators;

/**
 * @brief default validator for email input elements
 */
class Email extends \Core\Htmlform\Validators\Validator {
	// {{{ validate()
	/**
	 * @brief email validation
	 *
	 * @param $email (string)
	 *        	email to be validated
	 * @param $parameters (array)
	 *        	validation parameters
	 * @return (bool) validation result
	 *        
	 */
	public function validate($email, $parameters = array()) {
		return ( bool ) filter_var ( $email, FILTER_VALIDATE_EMAIL );
	}
	// }}}
}
