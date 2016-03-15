<?php

namespace Widget\Form\Phparray;

abstract class Validator extends \Core\Base\Action {

	protected $values = array();
	protected $errors;
	
	public function __construct($values = array()) {
		parent::__construct();
		$this->values = $values;
	}
	
	public function isValid() {
		return true;
	}
	
	public function getError() {
		return $this->errors ? implode('<br />', $this->errors) : null;
	}
	
}