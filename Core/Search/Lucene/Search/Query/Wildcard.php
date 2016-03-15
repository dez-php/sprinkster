<?php

namespace Core\Search\Lucene\Search\Query;

class Wildcard extends \Core\Search\Lucene\Search\Query {
	/**
	 * Search pattern.
	 *
	 * Field has to be fully specified or has to be null
	 * Text may contain '*' or '?' symbols
	 *
	 * @var \Core\Search\Lucene\Index\Term
	 */
	private $_pattern;
	
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
	private $_matches = null;
	
	/**
	 * \Core\Search\Lucene\Search\Query\Wildcard constructor.
	 *
	 * @param \Core\Search\Lucene\Index\Term $pattern        	
	 */
	public function __construct(\Core\Search\Lucene\Index\Term $pattern) {
		$this->_pattern = $pattern;
	}
	
	/**
	 * Get terms prefix
	 *
	 * @param string $word        	
	 * @return string
	 */
	private static function _getPrefix($word) {
		$questionMarkPosition = strpos ( $word, '?' );
		$astrericPosition = strpos ( $word, '*' );
		
		if ($questionMarkPosition !== false) {
			if ($astrericPosition !== false) {
				return substr ( $word, 0, min ( $questionMarkPosition, $astrericPosition ) );
			}
			
			return substr ( $word, 0, $questionMarkPosition );
		} else if ($astrericPosition !== false) {
			return substr ( $word, 0, $astrericPosition );
		}
		
		return $word;
	}
	
	/**
	 * Re-write query into primitive queries in the context of specified index
	 *
	 * @param \Core\Search\Lucene\InterfaceLucene $index        	
	 * @return \Core\Search\Lucene\Search\Query
	 */
	public function rewrite(\Core\Search\Lucene\InterfaceLucene $index) {
		$this->_matches = array ();
		
		if ($this->_pattern->field === null) {
			// Search through all fields
			$fields = $index->getFieldNames ( true /* indexed fields list */);
		} else {
			$fields = array (
					$this->_pattern->field 
			);
		}
		
		$prefix = self::_getPrefix ( $this->_pattern->text );
		$prefixLength = strlen ( $prefix );
		$matchExpression = '/^' . str_replace ( array (
				'\\?',
				'\\*' 
		), array (
				'.',
				'.*' 
		), preg_quote ( $this->_pattern->text, '/' ) ) . '$/';
		
		/**
		 * @todo check for PCRE unicode support may be performed through
		 * JO_Environment in some future
		 */
		if (@preg_match ( '/\pL/u', 'a' ) == 1) {
			// PCRE unicode support is turned on
			// add Unicode modifier to the match expression
			$matchExpression .= 'u';
		}
		
		foreach ( $fields as $field ) {
			$index->resetTermsStream ();
			
			if ($prefix != '') {
				$index->skipTo ( new \Core\Search\Lucene\Index\Term ( $prefix, $field ) );
				
				while ( $index->currentTerm () !== null && $index->currentTerm ()->field == $field && substr ( $index->currentTerm ()->text, 0, $prefixLength ) == $prefix ) {
					if (preg_match ( $matchExpression, $index->currentTerm ()->text ) === 1) {
						$this->_matches [] = $index->currentTerm ();
					}
					
					$index->nextTerm ();
				}
			} else {
				$index->skipTo ( new \Core\Search\Lucene\Index\Term ( '', $field ) );
				
				while ( $index->currentTerm () !== null && $index->currentTerm ()->field == $field ) {
					if (preg_match ( $matchExpression, $index->currentTerm ()->text ) === 1) {
						$this->_matches [] = $index->currentTerm ();
					}
					
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
		throw new \Core\Search\Lucene\Exception ( 'Wildcard query should not be directly used for search. Use $query->rewrite($index)' );
	}
	
	/**
	 * Returns query pattern
	 *
	 * @return \Core\Search\Lucene\Index\Term
	 */
	public function getPattern() {
		return $this->_pattern;
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
		throw new \Core\Search\Lucene\Exception ( 'Wildcard query should not be directly used for search. Use $query->rewrite($index)' );
	}
	
	/**
	 * Execute query in context of index reader
	 * It also initializes necessary internal structures
	 *
	 * @param \Core\Search\Lucene\InterfaceLucene $reader        	
	 * @throws \Core\Search\Lucene\Exception
	 */
	public function execute(\Core\Search\Lucene\InterfaceLucene $reader) {
		throw new \Core\Search\Lucene\Exception ( 'Wildcard query should not be directly used for search. Use $query->rewrite($index)' );
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
		throw new \Core\Search\Lucene\Exception ( 'Wildcard query should not be directly used for search. Use $query->rewrite($index)' );
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
		throw new \Core\Search\Lucene\Exception ( 'Wildcard query should not be directly used for search. Use $query->rewrite($index)' );
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
		
		$matchExpression = '/^' . str_replace ( array (
				'\\?',
				'\\*' 
		), array (
				'.',
				'.*' 
		), preg_quote ( $this->_pattern->text, '/' ) ) . '$/';
		if (@preg_match ( '/\pL/u', 'a' ) == 1) {
			// PCRE unicode support is turned on
			// add Unicode modifier to the match expression
			$matchExpression .= 'u';
		}
		
		$tokens = \Core\Search\Lucene\Analysis\Analyzer::getDefault ()->tokenize ( $doc->getFieldUtf8Value ( 'body' ), 'UTF-8' );
		foreach ( $tokens as $token ) {
			if (preg_match ( $matchExpression, $token->getTermText () ) === 1) {
				$words [] = $token->getTermText ();
			}
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
		return (($this->_pattern->field === null) ? '' : $this->_pattern->field . ':') . $this->_pattern->text;
	}
}

