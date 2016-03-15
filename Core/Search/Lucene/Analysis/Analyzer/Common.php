<?php

namespace Core\Search\Lucene\Analysis\Analyzer;

abstract class Common extends \Core\Search\Lucene\Analysis\Analyzer {
	/**
	 * The set of Token filters applied to the Token stream.
	 * Array of \Core\Search\Lucene\Analysis\TokenFilter objects.
	 *
	 * @var array
	 */
	private $_filters = array ();
	
	/**
	 * Add Token filter to the Analyzer
	 *
	 * @param \Core\Search\Lucene\Analysis\TokenFilter $filter        	
	 */
	public function addFilter(\Core\Search\Lucene\Analysis\TokenFilter $filter) {
		$this->_filters [] = $filter;
	}
	
	/**
	 * Apply filters to the token.
	 * Can return null when the token was removed.
	 *
	 * @param \Core\Search\Lucene\Analysis\Token $token        	
	 * @return \Core\Search\Lucene\Analysis\Token
	 */
	public function normalize(\Core\Search\Lucene\Analysis\Token $token) {
		foreach ( $this->_filters as $filter ) {
			$token = $filter->normalize ( $token );
			
			// resulting token can be null if the filter removes it
			if (is_null ( $token )) {
				return null;
			}
		}
		
		return $token;
	}
}

