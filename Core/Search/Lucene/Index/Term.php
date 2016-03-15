<?php

namespace Core\Search\Lucene\Index;

class Term {
	/**
	 * Field name or field number (depending from context)
	 *
	 * @var mixed
	 */
	public $field;
	
	/**
	 * Term value
	 *
	 * @var string
	 */
	public $text;
	
	/**
	 * Object constructor
	 */
	public function __construct($text, $field = null) {
		$this->field = ($field === null) ? \Core\Search\Lucene::getDefaultSearchField () : $field;
		$this->text = $text;
	}
	
	/**
	 * Returns term key
	 *
	 * @return string
	 */
	public function key() {
		return $this->field . chr ( 0 ) . $this->text;
	}
	
	/**
	 * Get term prefix
	 *
	 * @param string $str        	
	 * @param integer $length        	
	 * @return string
	 */
	public static function getPrefix($str, $length) {
		$prefixBytes = 0;
		$prefixChars = 0;
		while ( $prefixBytes < strlen ( $str ) && $prefixChars < $length ) {
			$charBytes = 1;
			if ((ord ( $str [$prefixBytes] ) & 0xC0) == 0xC0) {
				$charBytes ++;
				if (ord ( $str [$prefixBytes] ) & 0x20) {
					$charBytes ++;
					if (ord ( $str [$prefixBytes] ) & 0x10) {
						$charBytes ++;
					}
				}
			}
			
			if ($prefixBytes + $charBytes > strlen ( $str )) {
				// wrong character
				break;
			}
			
			$prefixChars ++;
			$prefixBytes += $charBytes;
		}
		
		return substr ( $str, 0, $prefixBytes );
	}
	
	/**
	 * Get UTF-8 string length
	 *
	 * @param string $str        	
	 * @return string
	 */
	public static function getLength($str) {
		$bytes = 0;
		$chars = 0;
		while ( $bytes < strlen ( $str ) ) {
			$charBytes = 1;
			if ((ord ( $str [$bytes] ) & 0xC0) == 0xC0) {
				$charBytes ++;
				if (ord ( $str [$bytes] ) & 0x20) {
					$charBytes ++;
					if (ord ( $str [$bytes] ) & 0x10) {
						$charBytes ++;
					}
				}
			}
			
			if ($bytes + $charBytes > strlen ( $str )) {
				// wrong character
				break;
			}
			
			$chars ++;
			$bytes += $charBytes;
		}
		
		return $chars;
	}
}

