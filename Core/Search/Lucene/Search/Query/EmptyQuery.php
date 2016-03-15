<?php

namespace Core\Search\Lucene\Search\Query;

class EmptyQuery extends \Core\Search\Lucene\Search\Query {
	/**
	 * Re-write query into primitive queries in the context of specified index
	 *
	 * @param \Core\Search\Lucene\InterfaceLucene $index        	
	 * @return \Core\Search\Lucene\Search\Query
	 */
	public function rewrite(\Core\Search\Lucene\InterfaceLucene $index) {
		return $this;
	}
	
	/**
	 * Optimize query in the context of specified index
	 *
	 * @param \Core\Search\Lucene\InterfaceLucene $index        	
	 * @return \Core\Search\Lucene\Search\Query
	 */
	public function optimize(\Core\Search\Lucene\InterfaceLucene $index) {
		// "Empty" query is a primitive query and don't need to be optimized
		return $this;
	}
	
	/**
	 * Constructs an appropriate Weight implementation for this query.
	 *
	 * @param \Core\Search\Lucene\InterfaceLucene $reader        	
	 * @return \Core\Search\Lucene\Search\Weight
	 */
	public function createWeight(\Core\Search\Lucene\InterfaceLucene $reader) {
		return new \Core\Search\Lucene\Search\Weight\EmptyWeight ();
	}
	
	/**
	 * Execute query in context of index reader
	 * It also initializes necessary internal structures
	 *
	 * @param \Core\Search\Lucene\InterfaceLucene $reader        	
	 */
	public function execute(\Core\Search\Lucene\InterfaceLucene $reader) {
		// Do nothing
	}
	
	/**
	 * Get document ids likely matching the query
	 *
	 * It's an array with document ids as keys (performance considerations)
	 *
	 * @return array
	 */
	public function matchedDocs() {
		return array ();
	}
	
	/**
	 * Score specified document
	 *
	 * @param integer $docId        	
	 * @param \Core\Search\Lucene\InterfaceLucene $reader        	
	 * @return float
	 */
	public function score($docId,\Core\Search\Lucene\InterfaceLucene $reader) {
		return 0;
	}
	
	/**
	 * Return query terms
	 *
	 * @return array
	 */
	public function getQueryTerms() {
		return array ();
	}
	
	/**
	 * Highlight query terms
	 *
	 * @param
	 *        	integer &$colorIndex
	 * @param \Core\Search\Lucene\Document\Html $doc        	
	 */
	public function highlightMatchesDOM(\Core\Search\Lucene\Document\Html $doc, &$colorIndex) {
		// Do nothing
	}
	
	/**
	 * Print a query
	 *
	 * @return string
	 */
	public function __toString() {
		return '<EmptyQuery>';
	}
}

