<?php

namespace Core\Search\Lucene\Search\Query;

class Range extends \Core\Search\Lucene\Search\Query {
	/**
	 * Lower term.
	 *
	 * @var \Core\Search\Lucene\Index\Term
	 */
	private $_lowerTerm;
	
	/**
	 * Upper term.
	 *
	 * @var \Core\Search\Lucene\Index\Term
	 */
	private $_upperTerm;
	
	/**
	 * Search field
	 *
	 * @var string
	 */
	private $_field;
	
	/**
	 * Inclusive
	 *
	 * @var boolean
	 */
	private $_inclusive;
	
	/**
	 * Matched terms.
	 *
	 * Matched terms list.
	 * It's filled during the search (rewrite operation) and may be used for
	 * search result
	 * post-processing
	 *
	 * Array of \Core\Search\Lucene\Index\Term objects
	 *
	 * @var array
	 */
	private $_matches;
	
	/**
	 * \Core\Search\Lucene\Search\Query\Range constructor.
	 *
	 * @param \Core\Search\Lucene\Index\Term|null $lowerTerm        	
	 * @param \Core\Search\Lucene\Index\Term|null $upperTerm        	
	 * @param boolean $inclusive        	
	 * @throws \Core\Search\Lucene\Exception
	 */
	public function __construct($lowerTerm, $upperTerm, $inclusive) {
		if ($lowerTerm === null && $upperTerm === null) {
			throw new \Core\Search\Lucene\Exception ( 'At least one term must be non-null' );
		}
		if ($lowerTerm !== null && $upperTerm !== null && $lowerTerm->field != $upperTerm->field) {
			throw new \Core\Search\Lucene\Exception ( 'Both terms must be for the same field' );
		}
		
		$this->_field = ($lowerTerm !== null) ? $lowerTerm->field : $upperTerm->field;
		$this->_lowerTerm = $lowerTerm;
		$this->_upperTerm = $upperTerm;
		$this->_inclusive = $inclusive;
	}
	
	/**
	 * Get query field name
	 *
	 * @return string null
	 */
	public function getField() {
		return $this->_field;
	}
	
	/**
	 * Get lower term
	 *
	 * @return \Core\Search\Lucene\Index\Term null
	 */
	public function getLowerTerm() {
		return $this->_lowerTerm;
	}
	
	/**
	 * Get upper term
	 *
	 * @return \Core\Search\Lucene\Index\Term null
	 */
	public function getUpperTerm() {
		return $this->_upperTerm;
	}
	
	/**
	 * Get upper term
	 *
	 * @return boolean
	 */
	public function isInclusive() {
		return $this->_inclusive;
	}
	
	/**
	 * Re-write query into primitive queries in the context of specified index
	 *
	 * @param \Core\Search\Lucene\InterfaceLucene $index        	
	 * @return \Core\Search\Lucene\Search\Query
	 */
	public function rewrite(\Core\Search\Lucene\InterfaceLucene $index) {
		$this->_matches = array ();
		
		if ($this->_field === null) {
			// Search through all fields
			$fields = $index->getFieldNames ( true /* indexed fields list */);
		} else {
			$fields = array (
					$this->_field 
			);
		}
		
		foreach ( $fields as $field ) {
			$index->resetTermsStream ();
			
			if ($this->_lowerTerm !== null) {
				$lowerTerm = new \Core\Search\Lucene\Index\Term ( $this->_lowerTerm->text, $field );
				
				$index->skipTo ( $lowerTerm );
				
				if (! $this->_inclusive && $index->currentTerm () == $lowerTerm) {
					// Skip lower term
					$index->nextTerm ();
				}
			} else {
				$index->skipTo ( new \Core\Search\Lucene\Index\Term ( '', $field ) );
			}
			
			if ($this->_upperTerm !== null) {
				// Walk up to the upper term
				$upperTerm = new \Core\Search\Lucene\Index\Term ( $this->_upperTerm->text, $field );
				
				while ( $index->currentTerm () !== null && $index->currentTerm ()->field == $field && $index->currentTerm ()->text < $upperTerm->text ) {
					$this->_matches [] = $index->currentTerm ();
					$index->nextTerm ();
				}
				
				if ($this->_inclusive && $index->currentTerm () == $upperTerm) {
					// Include upper term into result
					$this->_matches [] = $upperTerm;
				}
			} else {
				// Walk up to the end of field data
				while ( $index->currentTerm () !== null && $index->currentTerm ()->field == $field ) {
					$this->_matches [] = $index->currentTerm ();
					$index->nextTerm ();
				}
			}
			
			$index->closeTermsStream ();
		}
		
		if (count ( $this->_matches ) == 0) {
			return new \Core\Search\Lucene\Search\Query\EmptyQuery ();
		} else if (count ( $this->_matches ) == 1) {
			return new \Core\Search\Lucene\Search\Query\Term ( reset ( $this->_matches ) );
		} else {
			$rewrittenQuery = new \Core\Search\Lucene\Search\Query\MultiTerm ();
			
			foreach ( $this->_matches as $matchedTerm ) {
				$rewrittenQuery->addTerm ( $matchedTerm );
			}
			
			return $rewrittenQuery;
		}
	}
	
	/**
	 * Optimize query in the context of specified index
	 *
	 * @param \Core\Search\Lucene\InterfaceLucene $index        	
	 * @return \Core\Search\Lucene\Search\Query
	 */
	public function optimize(\Core\Search\Lucene\InterfaceLucene $index) {
		throw new \Core\Search\Lucene\Exception ( 'Range query should not be directly used for search. Use $query->rewrite($index)' );
	}
	
	/**
	 * Return query terms
	 *
	 * @return array
	 * @throws \Core\Search\Lucene\Exception
	 */
	public function getQueryTerms() {
		if ($this->_matches === null) {
			throw new \Core\Search\Lucene\Exception ( 'Search has to be performed first to get matched terms' );
		}
		
		return $this->_matches;
	}
	
	/**
	 * Constructs an appropriate Weight implementation for this query.
	 *
	 * @param \Core\Search\Lucene\InterfaceLucene $reader        	
	 * @return \Core\Search\Lucene\Search\Weight
	 * @throws \Core\Search\Lucene\Exception
	 */
	public function createWeight(\Core\Search\Lucene\InterfaceLucene $reader) {
		throw new \Core\Search\Lucene\Exception ( 'Range query should not be directly used for search. Use $query->rewrite($index)' );
	}
	
	/**
	 * Execute query in context of index reader
	 * It also initializes necessary internal structures
	 *
	 * @param \Core\Search\Lucene\InterfaceLucene $reader        	
	 * @throws \Core\Search\Lucene\Exception
	 */
	public function execute(\Core\Search\Lucene\InterfaceLucene $reader) {
		throw new \Core\Search\Lucene\Exception ( 'Range query should not be directly used for search. Use $query->rewrite($index)' );
	}
	
	/**
	 * Get document ids likely matching the query
	 *
	 * It's an array with document ids as keys (performance considerations)
	 *
	 * @return array
	 * @throws \Core\Search\Lucene\Exception
	 */
	public function matchedDocs() {
		throw new \Core\Search\Lucene\Exception ( 'Range query should not be directly used for search. Use $query->rewrite($index)' );
	}
	
	/**
	 * Score specified document
	 *
	 * @param integer $docId        	
	 * @param \Core\Search\Lucene\InterfaceLucene $reader        	
	 * @return float
	 * @throws \Core\Search\Lucene\Exception
	 */
	public function score($docId,\Core\Search\Lucene\InterfaceLucene $reader) {
		throw new \Core\Search\Lucene\Exception ( 'Range query should not be directly used for search. Use $query->rewrite($index)' );
	}
	
	/**
	 * Highlight query terms
	 *
	 * @param
	 *        	integer &$colorIndex
	 * @param \Core\Search\Lucene\Document\Html $doc        	
	 */
	public function highlightMatchesDOM(\Core\Search\Lucene\Document\Html $doc, &$colorIndex) {
		$words = array ();
		
		foreach ( $this->_matches as $term ) {
			$words [] = $term->text;
		}
		
		$doc->highlight ( $words, $this->_getHighlightColor ( $colorIndex ) );
	}
	
	/**
	 * Print a query
	 *
	 * @return string
	 */
	public function __toString() {
		// It's used only for query visualisation, so we don't care about
		// characters escaping
		return (($this->_field === null) ? '' : $this->_field . ':') . (($this->_inclusive) ? '[' : '{') . (($this->_lowerTerm !== null) ? $this->_lowerTerm->text : 'null') . ' TO ' . (($this->_upperTerm !== null) ? $this->_upperTerm->text : 'null') . (($this->_inclusive) ? ']' : '}');
	}
}

