<?php

namespace Core\Search\Lucene\Search\QueryEntry;

class Phrase extends \Core\Search\Lucene\Search\QueryEntry {
	/**
	 * Phrase value
	 *
	 * @var string
	 */
	private $_phrase;
	
	/**
	 * Field
	 *
	 * @var string null
	 */
	private $_field;
	
	/**
	 * Proximity phrase query
	 *
	 * @var boolean
	 */
	private $_proximityQuery = false;
	
	/**
	 * Words distance, used for proximiti queries
	 *
	 * @var integer
	 */
	private $_wordsDistance = 0;
	
	/**
	 * Object constractor
	 *
	 * @param string $phrase        	
	 * @param string $field        	
	 */
	public function __construct($phrase, $field) {
		$this->_phrase = $phrase;
		$this->_field = $field;
	}
	
	/**
	 * Process modifier ('~')
	 *
	 * @param mixed $parameter        	
	 */
	public function processFuzzyProximityModifier($parameter = null) {
		$this->_proximityQuery = true;
		
		if ($parameter !== null) {
			$this->_wordsDistance = $parameter;
		}
	}
	
	/**
	 * Transform entry to a subquery
	 *
	 * @param string $encoding        	
	 * @return \Core\Search\Lucene\Search\Query
	 * @throws \Core\Search\Lucene\Search\QueryParserException
	 */
	public function getQuery($encoding) {
		if (strpos ( $this->_phrase, '?' ) !== false || strpos ( $this->_phrase, '*' ) !== false) {
			throw new \Core\Search\Lucene\Search\QueryParserException ( 'Wildcards are only allowed in a single terms.' );
		}
		
		$tokens = \Core\Search\Lucene\Analysis\Analyzer::getDefault ()->tokenize ( $this->_phrase, $encoding );
		
		if (count ( $tokens ) == 0) {
			return new \Core\Search\Lucene\Search\Query\Insignificant ();
		}
		
		if (count ( $tokens ) == 1) {
			$term = new \Core\Search\Lucene\Index\Term ( $tokens [0]->getTermText (), $this->_field );
			$query = new \Core\Search\Lucene\Search\Query\Term ( $term );
			$query->setBoost ( $this->_boost );
			
			return $query;
		}
		
		// It's not empty or one term query
		$position = - 1;
		$query = new \Core\Search\Lucene\Search\Query\Phrase ();
		foreach ( $tokens as $token ) {
			$position += $token->getPositionIncrement ();
			$term = new \Core\Search\Lucene\Index\Term ( $token->getTermText (), $this->_field );
			$query->addTerm ( $term, $position );
		}
		
		if ($this->_proximityQuery) {
			$query->setSlop ( $this->_wordsDistance );
		}
		
		$query->setBoost ( $this->_boost );
		
		return $query;
	}
}
