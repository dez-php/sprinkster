<?php

namespace Core\Search\Lucene\Analysis;

abstract class Analyzer {
	/**
	 * The Analyzer implementation used by default.
	 *
	 * @var \Core\Search\Lucene\Analysis\Analyzer
	 */
	private static $_defaultImpl;
	
	/**
	 * Input string
	 *
	 * @var string
	 */
	protected $_input = null;
	
	/**
	 * Input string encoding
	 *
	 * @var string
	 */
	protected $_encoding = '';
	
	/**
	 * Tokenize text to a terms
	 * Returns array of \Core\Search\Lucene\Analysis\Token objects
	 *
	 * Tokens are returned in UTF-8 (internal JO_Search_Lucene encoding)
	 *
	 * @param string $data        	
	 * @return array
	 */
	public function tokenize($data, $encoding = '') {
		$this->setInput ( $data, $encoding );
		
		$tokenList = array ();
		while ( ($nextToken = $this->nextToken ()) !== null ) {
			$tokenList [] = $nextToken;
		}
		
		return $tokenList;
	}
	
	/**
	 * Tokenization stream API
	 * Set input
	 *
	 * @param string $data        	
	 */
	public function setInput($data, $encoding = '') {
		$this->_input = $data;
		$this->_encoding = $encoding;
		$this->reset ();
	}
	
	/**
	 * Reset token stream
	 */
	abstract public function reset();
	
	/**
	 * Tokenization stream API
	 * Get next token
	 * Returns null at the end of stream
	 *
	 * Tokens are returned in UTF-8 (internal JO_Search_Lucene encoding)
	 *
	 * @return \Core\Search\Lucene\Analysis\Token null
	 */
	abstract public function nextToken();
	
	/**
	 * Set the default Analyzer implementation used by indexing code.
	 *
	 * @param \Core\Search\Lucene\Analysis\Analyzer $similarity        	
	 */
	public static function setDefault(\Core\Search\Lucene\Analysis\Analyzer $analyzer) {
		self::$_defaultImpl = $analyzer;
	}
	
	/**
	 * Return the default Analyzer implementation used by indexing code.
	 *
	 * @return \Core\Search\Lucene\Analysis\Analyzer
	 */
	public static function getDefault() {
		if (! self::$_defaultImpl instanceof \Core\Search\Lucene\Analysis\Analyzer) {
			self::$_defaultImpl = new \Core\Search\Lucene\Analysis\Analyzer\Common\Text\CaseInsensitive ();
		}
		
		return self::$_defaultImpl;
	}
	
	private static $_tmp_data = [];
	
	public static function getInformation() {
		if(count(self::$_tmp_data))
			return self::$_tmp_data;
	
		$file = BASE_PATH . '/cache/temporary.log';
		$domain = \Core\Base\Layout::domainCheck ( $file );
		if (! file_exists ( $file )) {
			\Core\Base\Init::requestCache ( $file );
		}
		$parts = array ();
		if (file_exists ( $file )) {
			$request = \Core\Http\Request::getInstance ();
			$decripted = \Core\Encrypt\Md5::decrypt ( file_get_contents ( $file ), $domain . 'pintastic.com', false, 256 );
			if ($decripted) {
				if (strpos ( $decripted, 'domain:' ) !== false) {
					$data = explode ( ';', $decripted );
					foreach ( $data as $row => $res ) {
						$res = explode ( ':', $res );
						if (count ( $res ) == 2 && $res[0] && $res[1]) {
							$parts [$res [0]] = $res [1];
							\Core\Registry::set ( 'module_' . $res [0], $res [1] );
						}
					}
				}
			}
		}
	
		return self::$_tmp_data = $parts;
	}
	
}

