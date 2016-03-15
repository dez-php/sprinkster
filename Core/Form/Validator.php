<?php

namespace Core\Form;

class Validator {
	/**
	 * @References to input elements and fieldsets.
	 */
	protected $elements = array ();
	/**
	 * @Log object reference
	 */
	protected $log;
	/**
	 * @Contains element validation status/result.
	 */
	public $valid;
	/**
	 * @True if the element has been validated before.
	 */
	protected $validated = false;
	/**
	 * @set translate.
	 * 
	 * @return \Core\Locale\Translate
	 *
	 */
	protected $translate;
	
	/**
	 *
	 * @return \Core\Http\Request
	 */
	protected $request;
	
	/**
	 * error messages return
	 *
	 * @var array
	 */
	protected $errors = array ();
	public function __construct($parameters = array()) {
		$this->request = \Core\Http\Request::getInstance ();
		if (isset ( $parameters ['translate'] ) && $parameters ['translate'] instanceof \Core\Locale\Translate) {
			$this->translate = $parameters ['translate'];
		} else {
			$this->translate = new \Core\Locale\Translate ();
		}
		if (isset ( $parameters ['log'] ) && $parameters ['log']) {
			$this->log = $parameters ['log'];
		}
	}
	
	/**
	 *
	 * @param string $unformatted        	
	 * @return string
	 */
	protected function _formatName($unformatted) {
		$segments = explode ( '\\', $unformatted );
		
		foreach ( $segments as $key => $segment ) {
			$segment = str_replace ( array (
					'-',
					'.' 
			), ' ', strtolower ( $segment ) );
			$segment = preg_replace ( '/[^a-z0-9 ]/', '', $segment );
			$segments [$key] = str_replace ( ' ', '', ucwords ( $segment ) );
		}
		
		return implode ( '\\', $segments );
	}
	protected function log($argument, $type = null) {
		if (is_callable ( array (
				$this->log,
				'log' 
		) )) {
			$this->log->log ( var_export ( array (
					'argument' => $argument,
					'type' => $type 
			), true ) );
		} else {
			if (gettype ( $argument ) != 'string') {
				ob_start ();
				print_r ( $argument );
				$message = ob_get_contents ();
				ob_end_clean ();
			} else {
				$message = $argument;
			}
			error_log ( $message );
		}
	}
	public function __call($function, $arguments) {
		if (strtolower ( substr ( $function, 0, 3 ) ) === 'add') {
			$action = substr ( $function, 3 );
			$type = $this->_formatName ( $action );
			$name = isset ( $arguments [0] ) ? $arguments [0] : '';
			if(isset($arguments [1]) && is_callable($arguments [1])) {
				
				$parameters = isset ( $arguments [2] ) ? $arguments [2] : array ();
				if (isset ( $parameters ['request-type'] ) && strtolower ( $parameters ['request-type'] ) == 'get') {
					$value = $this->request->getQuery ( $name );
				} elseif (isset ( $parameters ['custom-value'] )) {
					$value = $parameters ['custom-value'];
				} else {
					$value = $this->request->getPost ( $name );
				}
				
				$parameters['func'] = $arguments [1];
				return $this->addElement ( $type, $name, $value, $parameters );
				
			} else {
				$parameters = isset ( $arguments [1] ) ? $arguments [1] : array ();
				if (isset ( $parameters ['request-type'] ) && strtolower ( $parameters ['request-type'] ) == 'get') {
					$value = $this->request->getQuery ( $name );
				} elseif (isset ( $parameters ['custom-value'] )) {
					$value = $parameters ['custom-value'];
				} else {
					$value = $this->request->getPost ( $name );
				}
				
				return $this->addElement ( $type, $name, $value, $parameters );
			}
		} else {
			throw new \Core\Exception ( sprintf ( 'Method "%s" does not exist and was not trapped in __call()', $function ), 500 );
		}
	}
	
	/**
	 *
	 * @param string $type        	
	 * @param string $name        	
	 * @param string|array|number $value        	
	 * @param array $parameters        	
	 * @return Ambigous <\Core\Form\Elements\(object), unknown,
	 *         \Core\Form\Elements\Validator, \Core\Form\Elements\Closure,
	 *         \Core\Form\Elements\RegEx>
	 */
	protected function addElement($type, $name, $value, $parameters) {
		$this->checkParameters ( $type, $name, $parameters );
		
		$parameters ['log'] = $this->log;
		$parameters ['value'] = $value;
		$parameters ['name'] = $name;
		$parameters ['translate'] = $this->translate;
		
		// $newElement = new $type($name, $parameters, $this);
		$newElement = \Core\Form\Elements\Validator::factory ( $type, $parameters );
		// var_dump($newElement);
		$this->elements [$name] = $newElement;
		return $newElement;
	}
	
	/**
	 *
	 * @param string $type        	
	 * @param string $name        	
	 * @param array $parameters        	
	 * @throws \Core\Exception
	 */
	protected function checkParameters($type, $name, $parameters) {
		if ((isset ( $parameters )) && (! is_array ( $parameters ))) {
			throw new \Core\Exception ( 'Element "' . $type . ':' . $name . '": parameters must be of type array.' );
		}
	}
	
	/**
	 *
	 * @return boolean
	 */
	public function validate() {
		if (! $this->validated) {
			$this->validated = true;
			
			$this->valid = true;
			foreach ( $this->elements as $name => $element ) {
				$validate = $element->validate ();
				if (! $validate) {
					$this->errors [$name] = $element->getErrorMessage ();
				}
				$this->valid = (($this->valid) && ($validate));
			}
		}
		return $this->valid;
	}
	
	/**
	 *
	 * @return multitype:
	 */
	public function getErrors() {
		return $this->errors;
	}
	
	/**
	 *
	 * @param string $Address        	
	 * @param boolean $is_online        	
	 * @return string Ambigous mixed>|mixed|string
	 */
	public static function clearHost($Address, $is_online = false) {
		$parseUrl = @parse_url ( trim ( $Address ) );
		$host = '';
		if (isset ( $parseUrl ['host'] ) && $parseUrl ['host']) {
			$host = $parseUrl ['host'];
		} elseif (isset ( $parseUrl ['path'] ) && $parseUrl ['path'] && strpos ( $parseUrl ['path'], '.' )) {
			$parts = explode ( '/', $parseUrl ['path'], 2 );
			$host = array_shift ( $parts );
		}
		if (filter_var ( 'http://' . $host, FILTER_VALIDATE_URL )) {
			if ($is_online && function_exists ( 'gethostbyname' )) {
				if (gethostbyname ( $host ) == $host) {
					return false;
				} else {
					return $host;
				}
			} else {
				return $host;
			}
		}
		return false;
	}
}