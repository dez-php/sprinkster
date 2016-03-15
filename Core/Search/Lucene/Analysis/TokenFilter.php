<?php

namespace Core\Search\Lucene\Analysis;

abstract class TokenFilter {
	/**
	 * Normalize Token or remove it (if null is returned)
	 *
	 * @param \Core\Search\Lucene\Analysis\Token $srcToken        	
	 * @return \Core\Search\Lucene\Analysis\Token
	 */
	abstract public function normalize(\Core\Search\Lucene\Analysis\Token $srcToken);
}

