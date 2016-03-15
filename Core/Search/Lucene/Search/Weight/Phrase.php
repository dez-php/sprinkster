<?php

namespace Core\Search\Lucene\Search\Weight;

class Phrase extends \Core\Search\Lucene\Search\Weight {
	/**
	 * IndexReader.
	 *
	 * @var \Core\Search\Lucene\InterfaceLucene
	 */
	private $_reader;
	
	/**
	 * The query that this concerns.
	 *
	 * @var \Core\Search\Lucene\Search\Query\Phrase
	 */
	private $_query;
	
	/**
	 * Score factor
	 *
	 * @var float
	 */
	private $_idf;
	
	/**
	 * \Core\Search\Lucene\Search\Weight\Phrase constructor
	 *
	 * @param \Core\Search\Lucene\Search\Query\Phrase $query        	
	 * @param \Core\Search\Lucene\InterfaceLucene $reader        	
	 */
	public function __construct(\Core\Search\Lucene\Search\Query\Phrase $query, \Core\Search\Lucene\InterfaceLucene $reader) {
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
		$this->_idf = $this->_reader->getSimilarity ()->idf ( $this->_query->getTerms (), $this->_reader );
		
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


