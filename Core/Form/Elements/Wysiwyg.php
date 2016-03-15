<?php

namespace Core\Form\Elements;

class Wysiwyg extends \Core\Form\Elements\Validator {
	
	/**
	 *
	 * @var null number
	 */
	protected $min = null;
	
	/**
	 *
	 * @var null number
	 */
	protected $max = null;
	
	/**
	 *
	 * @var null string
	 */
	protected $error;
	
	/**
	 *
	 * @var null string
	 */
	protected $error_text_min = null;
	
	/**
	 *
	 * @var null string
	 */
	protected $error_text_max = null;
	
	/**
	 * @ url validator
	 *
	 * @return (bool) validation result
	 *        
	 */
	public function validate()
	{
		$this->value = html_entity_decode(strip_tags($this->value));

		if($this->min !== null && mb_strlen ( $this->value, 'utf-8' ) < $this->min)
		{
			$this->error = sprintf ( $this->error_text_min ? $this->error_text_min : $this->translate->_ ( 'Field must contain not less than %d characters' ), $this->min );
			return FALSE;
		}

		if($this->max !== null && mb_strlen ( $this->value, 'utf-8' ) > $this->max)
		{
			$this->error = sprintf ( $this->error_text_max ? $this->error_text_max : $this->translate->_ ( 'Field must contain no more than %d characters' ), $this->min );
			return FALSE;
		}

		return TRUE;
	}
	
	/**
	 *
	 * @return (string)
	 */
	public function getErrorMessage()
	{
		return $this->error;
	}
}