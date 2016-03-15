<?php
namespace Core\Form\Elements;

class Email extends \Core\Form\Elements\Validator {
	
	/**
	 *
	 * @var null string
	 */
	protected $error_text = null;
	
	public function validate()
	{
		return ( bool ) filter_var ( $this->value, FILTER_VALIDATE_EMAIL );
	}
	
	public function getErrorMessage()
	{
		return $this->error_text ? $this->error_text : $this->translate->_ ( 'Please enter a valid e-mail address' );
	}
}
