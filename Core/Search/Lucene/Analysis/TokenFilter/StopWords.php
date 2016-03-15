<?php

namespace Core\Search\Lucene\Analysis\TokenFilter;

class StopWords extends \Core\Search\Lucene\Analysis\TokenFilter {
	/**
	 * Stop Words
	 * 
	 * @var array
	 */
	private $_stopSet;
	
	/**
	 * Constructs new instance of this filter.
	 *
	 * @param array $stopwords
	 *        	array (set) of words that will be filtered out
	 */
	public function __construct($stopwords = array()) {
		$this->_stopSet = array_flip ( $stopwords );
	}
	
	/**
	 * Normalize Token or remove it (if null is returned)
	 *
	 * @param \Core\Search\Lucene\Analysis\Token $srcToken        	
	 * @return \Core\Search\Lucene\Analysis\Token
	 */
	public function normalize(\Core\Search\Lucene\Analysis\Token $srcToken) {
		if (array_key_exists ( $srcToken->getTermText (), $this->_stopSet )) {
			return null;
		} else {
			return $srcToken;
		}
	}
	
	/**
	 * Fills stopwords set from a text file.
	 * Each line contains one stopword, lines with '#' in the first
	 * column are ignored (as comments).
	 *
	 * You can call this method one or more times. New stopwords are always
	 * added to current set.
	 *
	 * @param string $filepath
	 *        	full path for text file with stopwords
	 * @throws \Core\Search\Exception When the file doesn`t exists or is not
	 *         readable.
	 */
	public function loadFromFile($filepath = null) {
		if (! $filepath || ! file_exists ( $filepath )) {
			throw new \Core\Search\Exception ( 'You have to provide valid file path' );
		}
		$fd = fopen ( $filepath, "r" );
		if (! $fd) {
			throw new \Core\Search\Exception ( 'Cannot open file ' . $filepath );
		}
		while ( ! feof ( $fd ) ) {
			$buffer = trim ( fgets ( $fd ) );
			if (strlen ( $buffer ) > 0 && $buffer [0] != '#') {
				$this->_stopSet [$buffer] = 1;
			}
		}
		if (! fclose ( $fd )) {
			throw new \Core\Search\Exception ( 'Cannot close file ' . $filepath );
		}
	}
}

