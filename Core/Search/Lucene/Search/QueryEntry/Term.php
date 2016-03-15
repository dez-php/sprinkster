<?php

namespace Core\Search\Lucene\Search\QueryEntry;

class Term extends \Core\Search\Lucene\Search\QueryEntry {
	/**
	 * Term value
	 *
	 * @var string
	 */
	private $_term;
	
	/**
	 * Field
	 *
	 * @var string null
	 */
	private $_field;
	
	/**
	 * Fuzzy search query
	 *
	 * @var boolean
	 */
	private $_fuzzyQuery = false;
	
	/**
	 * Similarity
	 *
	 * @var float
	 */
	private $_similarity = 1.;
	
	/**
	 * Object constractor
	 *
	 * @param string $term        	
	 * @param string $field        	
	 */
	public function __construct($term, $field) {
		$this->_term = $term;
		$this->_field = $field;
	}
	
	/**
	 * Process modifier ('~')
	 *
	 * @param mixed $parameter        	
	 */
	public function processFuzzyProximityModifier($parameter = null) {
		$this->_fuzzyQuery = true;
		
		if ($parameter !== null) {
			$this->_similarity = $parameter;
		} else {
			$this->_similarity = \Core\Search\Lucene\Search\Query\Fuzzy::DEFAULT_MIN_SIMILARITY;
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
		if (strpos ( $this->_term, '?' ) !== false || strpos ( $this->_term, '*' ) !== false) {
			if ($this->_fuzzyQuery) {
				throw new \Core\Search\Lucene\Search\QueryParserException ( 'Fuzzy search is not supported for terms with wildcards.' );
			}
			
			$pattern = '';
			
			$subPatterns = explode ( '*', $this->_term );
			
			$astericFirstPass = true;
			foreach ( $subPatterns as $subPattern ) {
				if (! $astericFirstPass) {
					$pattern .= '*';
				} else {
					$astericFirstPass = false;
				}
				
				$subPatternsL2 = explode ( '?', $subPattern );
				
				$qMarkFirstPass = true;
				foreach ( $subPatternsL2 as $subPatternL2 ) {
					if (! $qMarkFirstPass) {
						$pattern .= '?';
					} else {
						$qMarkFirstPass = false;
					}
					
					$tokens = \Core\Search\Lucene\Analysis\Analyzer::getDefault ()->tokenize ( $subPatternL2, $encoding );
					if (count ( $tokens ) > 1) {
						throw new \Core\Search\Lucene\Search\QueryParserException ( 'Wildcard search is supported only for non-multiple word terms' );
					}
					
					foreach ( $tokens as $token ) {
						$pattern .= $token->getTermText ();
					}
				}
			}
			
			$term = new \Core\Search\Lucene\Index\Term ( $pattern, $this->_field );
			$query = new \Core\Search\Lucene\Search\Query\Wildcard ( $term );
			$query->setBoost ( $this->_boost );
			
			return $query;
		}
		
		$tokens = \Core\Search\Lucene\Analysis\Analyzer::getDefault ()->tokenize ( $this->_term, $encoding );
		
		if (count ( $tokens ) == 0) {
			return new \Core\Search\Lucene\Search\Query\Insignificant ();
		}
		
		if (count ( $tokens ) == 1 && ! $this->_fuzzyQuery) {
			$term = new \Core\Search\Lucene\Index\Term ( $tokens [0]->getTermText (), $this->_field );
			$query = new \Core\Search\Lucene\Search\Query\Term ( $term );
			$query->setBoost ( $this->_boost );
			
			return $query;
		}
		
		if (count ( $tokens ) == 1 && $this->_fuzzyQuery) {
			$term = new \Core\Search\Lucene\Index\Term ( $tokens [0]->getTermText (), $this->_field );
			$query = new \Core\Search\Lucene\Search\Query\Fuzzy ( $term, $this->_similarity );
			$query->setBoost ( $this->_boost );
			
			return $query;
		}
		
		if ($this->_fuzzyQuery) {
			throw new \Core\Search\Lucene\Search\QueryParserException ( 'Fuzzy search is supported only for non-multiple word terms' );
		}
		
		// It's not empty or one term query
		$query = new \Core\Search\Lucene\Search\Query\MultiTerm ();
		
		/**
		 *
		 * @todo Process $token->getPositionIncrement() to support stemming,
		 *       synonyms and other
		 *       analizer design features
		 */
		foreach ( $tokens as $token ) {
			$term = new \Core\Search\Lucene\Index\Term ( $token->getTermText (), $this->_field );
			$query->addTerm ( $term, true ); // all subterms are required
		}
		
		$query->setBoost ( $this->_boost );
		
		return $query;
	}
}
