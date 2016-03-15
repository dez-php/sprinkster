<?php

namespace Core\Search\Lucene\Search;

abstract class QueryEntry {
	/**
	 * Query entry boost factor
	 *
	 * @var float
	 */
	protected $_boost = 1.0;
	
	/**
	 * Process modifier ('~')
	 *
	 * @param mixed $parameter        	
	 */
	abstract public function processFuzzyProximityModifier($parameter = null);
	
	/**
	 * Transform entry to a subquery
	 *
	 * @param string $encoding        	
	 * @return \Core\Search\Lucene\Search\Query
	 */
	abstract public function getQuery($encoding);
	
	/**
	 * Boost query entry
	 *
	 * @param float $boostFactor        	
	 */
	public function boost($boostFactor) {
		$this->_boost *= $boostFactor;
	}
}
