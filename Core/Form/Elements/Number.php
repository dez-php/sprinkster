<?php

namespace Core\Form\Elements;

/**
 * @ default validator for number input elements
 */
class Number extends \Core\Form\Elements\Validator {
	
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
	protected $error_text = null;
	
	// {{{ validate()
	/**
	 * @ number validation
	 *
	 * @return (bool) validation result
	 *        
	 */
	public function validate() {
		$min = $this->min;
		$max = $this->max;
		$value = $this->value;
		
		return is_numeric ( $value ) && (($value >= $min) || ($min === null)) && (($value <= $max) || ($max === null));
	}
	// }}}
	
	/**
	 *
	 * @return (string)
	 */
	public function getErrorMessage() {
		return $this->error_text ? $this->error_text : $this->translate->_ ( 'Please enter a valid number' );
	}
	
	// {{{ typeCastValue()
/**
 * @ Converts value to element specific type.
 *
 * Based on (parseFloat)
 * http://www.php.net/manual/en/function.floatval.php#84793
 *
 * @return void
 *
 */
	/*
	 * protected function typeCastValue() { $ptString = $this->value; $pString =
	 * str_replace(" ", "", $ptString); if (substr_count($pString, ",") > 1)
	 * {$pString = str_replace(",", "", $pString);} if (substr_count($pString,
	 * ".") > 1) {$pString = str_replace(".", "", $pString);} $pregResult =
	 * array(); $commaset = strpos($pString,','); if ($commaset === false)
	 * {$commaset = -1;} $pointset = strpos($pString,'.'); if ($pointset ===
	 * false) {$pointset = -1;} $pregResultA = array(); $pregResultB = array();
	 * if ($pointset < $commaset) {
	 * preg_match('#(([-]?[0-9]+(\.[0-9])?)+(,[0-9]+)?)#', $pString,
	 * $pregResultA); } preg_match('#(([-]?[0-9]+(,[0-9])?)+(\.[0-9]+)?)#',
	 * $pString, $pregResultB); if ((isset($pregResultA[0]) &&
	 * (!isset($pregResultB[0]) || strstr($pregResultA[0],$pregResultB[0]) == 0
	 * || !$pointset))) { $numberString = $pregResultA[0]; $numberString =
	 * str_replace('.','',$numberString); $numberString =
	 * str_replace(',','.',$numberString); } elseif (isset($pregResultB[0]) &&
	 * (!isset($pregResultA[0]) || strstr($pregResultB[0],$pregResultA[0]) == 0
	 * || !$commaset)) { $numberString = $pregResultB[0]; $numberString =
	 * str_replace(',','',$numberString); } $this->value = (float)
	 * isset($numberString)?$numberString:null; }
	 */
	// }}}
}
