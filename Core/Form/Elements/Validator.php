<?php

namespace Core\Form\Elements;

/**
 * @brief parent validator class
 *
 * Basic validator. Contaіns validator factory.
 */
class Validator {
	// {{{ variables
	/**
	 * @ log object
	 */
	protected $log = null;
	// }}}
	/**
	 * @Input elements's value.
	 */
	protected $value = null;
	/**
	 * @set translate.
	 * 
	 * @return \Core\Locale\Translate
	 *
	 */
	protected $translate = null;
	/**
	 *
	 * @var null array
	 */
	protected $parameters = null;
	
	// {{{ __construct()
	/**
	 * @ validator constructor
	 *
	 * Attaches error logging object to validator.
	 *
	 * @param $log (object)
	 *        	error logging object
	 * @return void
	 *
	 */
	public function __construct($log = null) {
		$this->translate = new \Core\Locale\Translate ();
		if ($log && is_array ( $log )) {
			foreach ( $log as $key => $value ) {
				$this->{$key} = $value;
			}
		} else {
			$this->log = $log;
		}
		$this->parameters = $log;
		$this->typeCastValue ();
	}
	// }}}
	
	// {{{ factory()
	/**
	 * @ valdiator object factory
	 *
	 * Static validator object factory. Picks validator type depending on
	 * $argument.
	 *
	 * @param $argument (string)
	 *        	validator type or regular expression or closure
	 * @param $log (object)
	 *        	error logging object
	 * @return (object) validator object
	 *        
	 */
	public static function factory($argument, $log = null) {
		if (! is_string ( $argument ) && is_callable ( $argument, false )) {
			$closureValidator = new \Core\Form\Elements\Closure ( $log );
			$closureValidator->setFunc ( $argument );
			
			return $closureValidator;
		} else if (($argument {0} === '/') && ($argument {strlen ( $argument ) - 1} === '/')) {
			$regExValidator = new \Core\Form\Elements\RegEx ( $log );
			$regExValidator->setRegEx ( $argument );
			
			return $regExValidator;
		} else {
			$type = '\Core\Form\Elements\\' . $argument;
			
			if (class_exists ( $type )) {
				return new $type ( $log );
			} else {
				return new Validator ( $log );
			}
		}
	}
	// }}}
	
	// {{{ validate()
	/**
	 * @ default validator.
	 *
	 * Everything is valid. To be overriden in specific validator objects.
	 *
	 * @param $value (mixed)
	 *        	value to be validated
	 * @param $parameters (array)
	 *        	validation parameters
	 * @return (bool) validation result
	 *        
	 */
	public function validate() {
		return true;
	}
	// }}}
	
	// {{{ log()
	/**
	 * @ error logging method
	 *
	 * @param $argument (string)
	 *        	error message
	 * @param $type (string)
	 *        	error type
	 * @return void
	 *
	 */
	protected function log($argument, $type) {
		if (is_callable ( array (
				$this->log,
				'log' 
		) )) {
			$this->log->log ( var_export ( array (
					'argument' => $argument,
					'type' => $type 
			), true ) );
		} else {
			error_log ( $argument );
		}
	}
	// }}}
	
	// {{{ getPatternAttribute()
	/**
	 * @ returns validators' regular expression as HTML5 pattern attribute
	 *
	 * @return (string) HTML pattern attribute
	 *        
	 */
	public function getPatternAttribute() {
		if (isset ( $this->regEx )) {
			return ' pattern="' . htmlspecialchars ( substr ( $this->regEx, 1, - 1 ), ENT_QUOTES, 'utf-8' ) . '"';
		}
	}
	// }}}
	
	/**
	 *
	 * @return (string)
	 */
	public function getErrorMessage() {
		return $this->translate->_ ( 'Please enter valid data' );
	}
	
	// {{{ typeCastValue()
	/**
	 * @brief converts element value
	 *
	 * Converts value to element specific datatype. (to be overridden by
	 * element child classes)
	 *
	 * @return void
	 *
	 */
	protected function typeCastValue() {
	}
	// }}}
}
