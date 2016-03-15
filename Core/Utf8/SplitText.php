<?php

namespace Core\Utf8;

class SplitText {
	protected $string = '';
	protected $length = 100;
	protected $comas = '...';
	public function __construct($string, $length = null, $comas = null) {
		if (! $length) {
			$length = $this->length;
		}
		if (! $comas) {
			$comas = $this->comas;
		}
		
		if ($length < mb_strlen ( $string, 'utf-8' )) {
			$words = $this->splitWords ( $string );
			$result = '';
			for($i = 0; $i < count ( $words ); $i ++) {
				$result .= $words [$i] . ' ';
				if (mb_strlen ( $result, 'utf-8' ) > $length) {
					$this->string = trim ( $result, ' ' ) . $comas;
					return $this;
				}
			}
			$this->string = trim ( $result, ' ' ) . $comas;
		} else {
			$this->string = $string;
		}
	}
	public function splitWords($string) {
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
		return $words;
	}
	public function __toString() {
		return $this->string;
	}
}