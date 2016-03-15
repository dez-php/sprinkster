<?php

namespace Core\Plugin;

class AbstractPlugin {
	protected $vars = array ();
	/**
	 *
	 * @var \Core\Locale\Translate
	 */
	protected $translate;
	
	/**
	 *
	 * @param array $options        	
	 */
	public function __construct($options = array()) {
		$this->setOptions ( $options );
		if (! ($this->translate instanceof \Core\Locale\Translate)) {
			$this->translate = new \Core\Locale\Translate ();
		}
	}
	
	/**
	 *
	 * @param array $options        	
	 * @return \Core\Plugin\AbstractPlugin
	 */
	public function setOptions($options = array()) {
		$class = get_class ( $this );
		$vars = get_class_vars ( $class );
		
		if (is_array ( $options )) {
			foreach ( $options as $name => $value ) {
				$method = 'set' . $name;
				if (method_exists ( $this, $method )) {
					$this->{$method} ( $value );
				} elseif (array_key_exists ( $name, $vars )) {
					$this->{$name} = $value;
				}
			}
		}
		return $this;
	}
	public function setTranslate($translate) {
		if ($translate instanceof \Core\Locale\Translate) {
			$this->translate = $translate;
		} else {
			$this->translate = new \Core\Locale\Translate ();
		}
		return $this;
	}
	public function _($message) {
		return $this->translate->_ ( $message );
	}
}