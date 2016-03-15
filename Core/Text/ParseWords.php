<?php

namespace Core\Text;

class ParseWords extends \ArrayObject {
	public function __construct($string) {
		$sizes = 0;
		$words = array ();
		for($i = 0; $i < mb_strlen ( $string, 'utf-8' ); $i ++) {
			$part = mb_strtolower ( mb_substr ( $string, $i, 1, 'utf-8' ), 'utf-8' );
			if (! isset ( $words [$sizes] )) {
				$words [$sizes] = '';
			}
			if (preg_match ( '/\p{L}+/u', $part, $match )) {
				$words [$sizes] .= $part;
			} else if (preg_match ( '/^([0-9]{1,})$/u', $part, $match )) {
				$words [$sizes] .= $part;
			} else {
				$sizes ++;
				continue;
			}
		}
		
		parent::__construct ( array_filter ( $words ) );
	}
	public function getMinLenght($simbols = 3) {
		$clone = array ();
		foreach ( $this as $key => $value ) {
			if (mb_strlen ( $value, 'utf-8' ) >= $simbols) {
				$clone [$key] = $value;
			}
		}
		return new \ArrayObject ( $clone );
	}
}

?>