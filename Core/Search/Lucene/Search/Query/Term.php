<?php

namespace Core\Search\Lucene\Search\Query;

class Term extends \Core\Search\Lucene\Search\Query {
	/**
	 * Term to find.
	 *
	 * @var \Core\Search\Lucene\Index\Term
	 */
	private $_term;
	
	/**
	 * Documents vector.
	 *
	 * @var array
	 */
	private $_docVector = null;
	
	/**
	 * Term freqs vector.
	 * array(docId => freq, ...)
	 *
	 * @var array
	 */
	private $_termFreqs;
	
	/**
	 * \Core\Search\Lucene\Search\Query\Term constructor
	 *
	 * @param \Core\Search\Lucene\Index\Term $term        	
	 * @param boolean $sign        	
	 */
	public function __construct(\Core\Search\Lucene\Index\Term $term) {
		$this->_term = $term;
	}
	
	/**
	 * Re-write query into primitive queries in the context of specified index
	 *
	 * @param \Core\Search\Lucene\InterfaceLucene $index        	
	 * @return \Core\Search\Lucene\Search\Query
	 */
	public function rewrite(\Core\Search\Lucene\InterfaceLucene $index) {
		if ($this->_term->field != null) {
			return $this;
		} else {
			$query = new \Core\Search\Lucene\Search\Query\MultiTerm ();
			$query->setBoost ( $this->getBoost () );
			
			foreach ( $index->getFieldNames ( true ) as $fieldName ) {
				$term = new \Core\Search\Lucene\Index\Term ( $this->_term->text, $fieldName );
				
				$query->addTerm ( $term );
			}
			
			return $query->rewrite ( $index );
		}
	}
	
	/**
	 * Optimize query in the context of specified index
	 *
	 * @param \Core\Search\Lucene\InterfaceLucene $index        	
	 * @return \Core\Search\Lucene\Search\Query
	 */
	public function optimize(\Core\Search\Lucene\InterfaceLucene $index) {
		// Check, that index contains specified term
		if (! $index->hasTerm ( $this->_term )) {
			return new \Core\Search\Lucene\Search\Query\EmptyQuery ();
		}
		
		return $this;
	}
	
	/**
	 * Constructs an appropriate Weight implementation for this query.
	 *
	 * @param \Core\Search\Lucene\InterfaceLucene $reader        	
	 * @return \Core\Search\Lucene\Search\Weight
	 */
	public function createWeight(\Core\Search\Lucene\InterfaceLucene $reader) {
		$this->_weight = new \Core\Search\Lucene\Search\Weight\Term ( $this->_term, $this, $reader );
		return $this->_weight;
	}
	
	/**
	 * Execute query in context of index reader
	 * It also initializes necessary internal structures
	 *
	 * @param \Core\Search\Lucene\InterfaceLucene $reader        	
	 */
	public function execute(\Core\Search\Lucene\InterfaceLucene $reader) {
		$this->_docVector = array_flip ( $reader->termDocs ( $this->_term ) );
		$this->_termFreqs = $reader->termFreqs ( $this->_term );
		
		// Initialize weight if it's not done yet
		$this->_initWeight ( $reader );
	}
	
	/**
	 * Get document ids likely matching the query
	 *
	 * It's an array with document ids as keys (performance considerations)
	 *
	 * @return array
	 */
	public function matchedDocs() {
		return $this->_docVector;
	}
	
	/**
	 * Score specified document
	 *
	 * @param integer $docId        	
	 * @param \Core\Search\Lucene\InterfaceLucene $reader        	
	 * @return float
	 */
	public function score($docId,\Core\Search\Lucene\InterfaceLucene $reader) {
		if (isset ( $this->_docVector [$docId] )) {
			return $reader->getSimilarity ()->tf ( $this->_termFreqs [$docId] ) * $this->_weight->getValue () * $reader->norm ( $docId, $this->_term->field ) * $this->getBoost ();
		} else {
			return 0;
		}
	}
	
	/**
	 * Return query terms
	 *
	 * @return array
	 */
	public function getQueryTerms() {
		return array (
				$this->_term 
		);
	}
	
	/**
	 * Return query term
	 *
	 * @return \Core\Search\Lucene\Index\Term
	 */
	public function getTerm() {
		return $this->_term;
	}
	
	/**
	 * Returns query term
	 *
	 * @return array
	 */
	public function getTerms() {
		return $this->_terms;
	}
	
	/**
	 * Highlight query terms
	 *
	 * @param
	 *        	integer &$colorIndex
	 * @param \Core\Search\Lucene\Document\Html $doc        	
	 */
	public function highlightMatchesDOM(\Core\Search\Lucene\Document\Html $doc, &$colorIndex) {
		$doc->highlight ( $this->_term->text, $this->_getHighlightColor ( $colorIndex ) );
	}
	
	/**
	 * Print a query
	 *
	 * @return string
	 */
	public function __toString() {
		// It's used only for query visualisation, so we don't care about
		// characters escaping
		return (($this->_term->field === null) ? '' : $this->_term->field . ':') . $this->_term->text;
	}
}

