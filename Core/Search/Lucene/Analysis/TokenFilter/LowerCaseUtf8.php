<?php

namespace Core\Search\Lucene\Analysis\TokenFilter;

class LowerCaseUtf8 extends \Core\Search\Lucene\Analysis\TokenFilter {
	/**
	 * Object constructor
	 */
	public function __construct() {
		if (! function_exists ( 'mb_strtolower' )) {
			// mbstring extension is disabled
			require_once 'Search/Lucene/Exception.php';
			throw new \Core\Search\Lucene\Exception ( 'Utf8 compatible lower case filter needs mbstring extension to be enabled.' );
		}
	}
	
	/**
	 * Normalize Token or remove it (if null is returned)
	 *
	 * @param \Core\Search\Lucene\Analysis\Token $srcToken        	
	 * @return \Core\Search\Lucene\Analysis\Token
	 */
	public function normalize(\Core\Search\Lucene\Analysis\Token $srcToken) {
		$newToken = new \Core\Search\Lucene\Analysis\Token ( mb_strtolower ( $srcToken->getTermText (), 'UTF-8' ), $srcToken->getStartOffset (), $srcToken->getEndOffset () );
		
		$newToken->setPositionIncrement ( $srcToken->getPositionIncrement () );
		
		return $newToken;
	}
}

