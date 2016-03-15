<?php

namespace Core\Search\Lucene\Search\Weight;

class Boolean extends \Core\Search\Lucene\Search\Weight {
	/**
	 * IndexReader.
	 *
	 * @var \Core\Search\Lucene\InterfaceLucene
	 */
	private $_reader;
	
	/**
	 * The query that this concerns.
	 *
	 * @var \Core\Search\Lucene\Search\Query
	 */
	private $_query;
	
	/**
	 * Queries weights
	 * Array of \Core\Search\Lucene\Search\Weight
	 *
	 * @var array
	 */
	private $_weights;
	
	/**
	 * \Core\Search\Lucene\Search\Weight\Boolean constructor
	 * query - the query that this concerns.
	 * reader - index reader
	 *
	 * @param \Core\Search\Lucene\Search\Query $query        	
	 * @param \Core\Search\Lucene\InterfaceLucene $reader        	
	 */
	public function __construct(\Core\Search\Lucene\Search\Query $query, \Core\Search\Lucene\InterfaceLucene $reader) {
		$this->_query = $query;
		$this->_reader = $reader;
		$this->_weights = array ();
		
		$signs = $query->getSigns ();
		
		foreach ( $query->getSubqueries () as $num => $subquery ) {
			if ($signs === null || $signs [$num] === null || $signs [$num]) {
				$this->_weights [$num] = $subquery->createWeight ( $reader );
			}
		}
	}
	
	/**
	 * The weight for this query
	 * Standard Weight::$_value is not used for boolean queries
	 *
	 * @return float
	 */
	public function getValue() {
		return $this->_query->getBoost ();
	}
	
	/**
	 * The sum of squared weights of contained query clauses.
	 *
	 * @return float
	 */
	public function sumOfSquaredWeights() {
		$sum = 0;
		foreach ( $this->_weights as $weight ) {
			// sum sub weights
			$sum += $weight->sumOfSquaredWeights ();
		}
		
		// boost each sub-weight
		$sum *= $this->_query->getBoost () * $this->_query->getBoost ();
		
		// check for empty query (like '-something -another')
		if ($sum == 0) {
			$sum = 1.0;
		}
		return $sum;
	}
	
	/**
	 * Assigns the query normalization factor to this.
	 *
	 * @param float $queryNorm        	
	 */
	public function normalize($queryNorm) {
		// incorporate boost
		$queryNorm *= $this->_query->getBoost ();
		
		foreach ( $this->_weights as $weight ) {
			$weight->normalize ( $queryNorm );
		}
	}
}


