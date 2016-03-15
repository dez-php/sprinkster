<?php

namespace Core\Search\Lucene\Search\Weight;

class Term extends \Core\Search\Lucene\Search\Weight {
	/**
	 * IndexReader.
	 *
	 * @var \Core\Search\Lucene\InterfaceLucene
	 */
	private $_reader;
	
	/**
	 * Term
	 *
	 * @var \Core\Search\Lucene\Index\Term
	 */
	private $_term;
	
	/**
	 * The query that this concerns.
	 *
	 * @var \Core\Search\Lucene\Search\Query
	 */
	private $_query;
	
	/**
	 * Score factor
	 *
	 * @var float
	 */
	private $_idf;
	
	/**
	 * Query weight
	 *
	 * @var float
	 */
	private $_queryWeight;
	
	/**
	 * \Core\Search\Lucene\Search\Weight\Term constructor
	 * reader - index reader
	 *
	 * @param \Core\Search\Lucene\Index\Term $term        	
	 * @param \Core\Search\Lucene\Search\Query $query        	
	 * @param \Core\Search\Lucene\InterfaceLucene $reader        	
	 */
	public function __construct(\Core\Search\Lucene\Index\Term $term, \Core\Search\Lucene\Search\Query $query, \Core\Search\Lucene\InterfaceLucene $reader) {
		$this->_term = $term;
		$this->_query = $query;
		$this->_reader = $reader;
	}
	
	/**
	 * The sum of squared weights of contained query clauses.
	 *
	 * @return float
	 */
	public function sumOfSquaredWeights() {
		// compute idf
		$this->_idf = $this->_reader->getSimilarity ()->idf ( $this->_term, $this->_reader );
		
		// compute query weight
		$this->_queryWeight = $this->_idf * $this->_query->getBoost ();
		
		// square it
		return $this->_queryWeight * $this->_queryWeight;
	}
	
	/**
	 * Assigns the query normalization factor to this.
	 *
	 * @param float $queryNorm        	
	 */
	public function normalize($queryNorm) {
		$this->_queryNorm = $queryNorm;
		
		// normalize query weight
		$this->_queryWeight *= $queryNorm;
		
		// idf for documents
		$this->_value = $this->_queryWeight * $this->_idf;
	}
}

