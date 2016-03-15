<?php

namespace Core\Search\Lucene\Analysis\TokenFilter;

class ShortWords extends \Core\Search\Lucene\Analysis\TokenFilter {
	/**
	 * Minimum allowed term length
	 * 
	 * @var integer
	 */
	private $length;
	
	/**
	 * Constructs new instance of this filter.
	 *
	 * @param integer $short
	 *        	minimum allowed length of term which passes this filter
	 *        	(default 2)
	 */
	public function __construct($length = 2) {
		$this->length = $length;
	}
	
	/**
	 * Normalize Token or remove it (if null is returned)
	 *
	 * @param \Core\Search\Lucene\Analysis\Token $srcToken        	
	 * @return \Core\Search\Lucene\Analysis\Token
	 */
	public function normalize(\Core\Search\Lucene\Analysis\Token $srcToken) {
		if (strlen ( $srcToken->getTermText () ) < $this->length) {
			return null;
		} else {
			return $srcToken;
		}
	}
}

