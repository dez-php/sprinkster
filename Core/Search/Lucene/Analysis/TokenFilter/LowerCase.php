<?php

namespace Core\Search\Lucene\Analysis\TokenFilter;

class LowerCase extends \Core\Search\Lucene\Analysis\TokenFilter {
	/**
	 * Normalize Token or remove it (if null is returned)
	 *
	 * @param \Core\Search\Lucene\Analysis\Token $srcToken        	
	 * @return \Core\Search\Lucene\Analysis\Token
	 */
	public function normalize(\Core\Search\Lucene\Analysis\Token $srcToken) {
		$newToken = new \Core\Search\Lucene\Analysis\Token ( strtolower ( $srcToken->getTermText () ), $srcToken->getStartOffset (), $srcToken->getEndOffset () );
		
		$newToken->setPositionIncrement ( $srcToken->getPositionIncrement () );
		
		return $newToken;
	}
}

