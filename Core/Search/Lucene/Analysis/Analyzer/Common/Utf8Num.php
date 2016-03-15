<?php

namespace Core\Search\Lucene\Analysis\Analyzer\Common;

class Utf8Num extends \Core\Search\Lucene\Analysis\Analyzer\Common {
	/**
	 * Current char position in an UTF-8 stream
	 *
	 * @var integer
	 */
	private $_position;
	
	/**
	 * Current binary position in an UTF-8 stream
	 *
	 * @var integer
	 */
	private $_bytePosition;
	
	/**
	 * Object constructor
	 *
	 * @throws \Core\Search\Lucene\Exception
	 */
	public function __construct() {
		if (@preg_match ( '/\pL/u', 'a' ) != 1) {
			// PCRE unicode support is turned off
			require_once 'Search/Lucene/Exception.php';
			throw new \Core\Search\Lucene\Exception ( 'Utf8Num analyzer needs PCRE unicode support to be enabled.' );
		}
	}
	
	/**
	 * Reset token stream
	 */
	public function reset() {
		$this->_position = 0;
		$this->_bytePosition = 0;
		
		// convert input into UTF-8
		if (strcasecmp ( $this->_encoding, 'utf8' ) != 0 && strcasecmp ( $this->_encoding, 'utf-8' ) != 0) {
			$this->_input = mb_convert_encoding ( $this->_input, 'UTF-8', mb_detect_encoding ( $this->_input ) );
			$this->_encoding = 'UTF-8';
		}
	}
	
	/**
	 * Tokenization stream API
	 * Get next token
	 * Returns null at the end of stream
	 *
	 * @return \Core\Search\Lucene\Analysis\Token null
	 */
	public function nextToken() {
		if ($this->_input === null) {
			return null;
		}
		
		do {
			if (! preg_match ( '/[\p{L}\p{N}]+/u', $this->_input, $match, PREG_OFFSET_CAPTURE, $this->_bytePosition )) {
				// It covers both cases a) there are no matches (preg_match(...)
				// === 0)
				// b) error occured (preg_match(...) === FALSE)
				return null;
			}
			
			// matched string
			$matchedWord = $match [0] [0];
			
			// binary position of the matched word in the input stream
			$binStartPos = $match [0] [1];
			
			// character position of the matched word in the input stream
			$startPos = $this->_position + mb_strlen ( mb_substr ( $this->_input, $this->_bytePosition, $binStartPos - $this->_bytePosition, 'UTF-8' ), 'UTF-8' );
			// character postion of the end of matched word in the input stream
			$endPos = $startPos + mb_strlen ( $matchedWord, 'UTF-8' );
			
			$this->_bytePosition = $binStartPos + strlen ( $matchedWord );
			$this->_position = $endPos;
			
			$token = $this->normalize ( new \Core\Search\Lucene\Analysis\Token ( $matchedWord, $startPos, $endPos ) );
		} while ( $token === null ); // try again if token is skipped
		
		return $token;
	}
}

