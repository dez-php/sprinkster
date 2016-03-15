<?php

namespace Core\Search\Lucene\Search;

abstract class Query {
	
	/**
	 * query boost factor
	 *
	 * @var float
	 */
	private $_boost = 1;
	
	/**
	 * Query weight
	 *
	 * @var \Core\Search\Lucene\Search\Weight
	 */
	protected $_weight = null;
	
	/**
	 * Current highlight color
	 *
	 * @var integer
	 */
	private $_currentColorIndex = 0;
	
	/**
	 * List of colors for text highlighting
	 *
	 * @var array
	 */
	private $_highlightColors = array (
			'#66ffff',
			'#ff66ff',
			'#ffff66',
			'#ff8888',
			'#88ff88',
			'#8888ff',
			'#88dddd',
			'#dd88dd',
			'#dddd88',
			'#aaddff',
			'#aaffdd',
			'#ddaaff',
			'#ddffaa',
			'#ffaadd',
			'#ffddaa' 
	);
	
	/**
	 * Gets the boost for this clause.
	 * Documents matching
	 * this clause will (in addition to the normal weightings) have their score
	 * multiplied by boost. The boost is 1.0 by default.
	 *
	 * @return float
	 */
	public function getBoost() {
		return $this->_boost;
	}
	
	/**
	 * Sets the boost for this query clause to $boost.
	 *
	 * @param float $boost        	
	 */
	public function setBoost($boost) {
		$this->_boost = $boost;
	}
	
	/**
	 * Score specified document
	 *
	 * @param integer $docId        	
	 * @param \Core\Search\Lucene\InterfaceLucene $reader        	
	 * @return float
	 */
	abstract public function score($docId,\Core\Search\Lucene\InterfaceLucene $reader);
	
	/**
	 * Get document ids likely matching the query
	 *
	 * It's an array with document ids as keys (performance considerations)
	 *
	 * @return array
	 */
	abstract public function matchedDocs();
	
	/**
	 * Execute query in context of index reader
	 * It also initializes necessary internal structures
	 *
	 * Query specific implementation
	 *
	 * @param \Core\Search\Lucene\InterfaceLucene $reader        	
	 */
	abstract public function execute(\Core\Search\Lucene\InterfaceLucene $reader);
	
	/**
	 * Constructs an appropriate Weight implementation for this query.
	 *
	 * @param \Core\Search\Lucene\InterfaceLucene $reader        	
	 * @return \Core\Search\Lucene\Search\Weight
	 */
	abstract public function createWeight(\Core\Search\Lucene\InterfaceLucene $reader);
	
	/**
	 * Constructs an initializes a Weight for a _top-level_query_.
	 *
	 * @param \Core\Search\Lucene\InterfaceLucene $reader        	
	 */
	protected function _initWeight(\Core\Search\Lucene\InterfaceLucene $reader) {
		// Check, that it's a top-level query and query weight is not
		// initialized yet.
		if ($this->_weight !== null) {
			return $this->_weight;
		}
		
		$this->createWeight ( $reader );
		$sum = $this->_weight->sumOfSquaredWeights ();
		$queryNorm = $reader->getSimilarity ()->queryNorm ( $sum );
		$this->_weight->normalize ( $queryNorm );
	}
	
	/**
	 * Re-write query into primitive queries in the context of specified index
	 *
	 * @param \Core\Search\Lucene\InterfaceLucene $index        	
	 * @return \Core\Search\Lucene\Search\Query
	 */
	abstract public function rewrite(\Core\Search\Lucene\InterfaceLucene $index);
	
	/**
	 * Optimize query in the context of specified index
	 *
	 * @param \Core\Search\Lucene\InterfaceLucene $index        	
	 * @return \Core\Search\Lucene\Search\Query
	 */
	abstract public function optimize(\Core\Search\Lucene\InterfaceLucene $index);
	
	/**
	 * Reset query, so it can be reused within other queries or
	 * with other indeces
	 */
	public function reset() {
		$this->_weight = null;
	}
	
	/**
	 * Print a query
	 *
	 * @return string
	 */
	abstract public function __toString();
	
	/**
	 * Return query terms
	 *
	 * @return array
	 */
	abstract public function getQueryTerms();
	
	/**
	 * Get highlight color and shift to next
	 *
	 * @param
	 *        	integer &$colorIndex
	 * @return string
	 */
	protected function _getHighlightColor(&$colorIndex) {
		$color = $this->_highlightColors [$colorIndex ++];
		
		$colorIndex %= count ( $this->_highlightColors );
		
		return $color;
	}
	
	/**
	 * Highlight query terms
	 *
	 * @param
	 *        	integer &$colorIndex
	 * @param \Core\Search\Lucene\Document\Html $doc        	
	 */
	abstract public function highlightMatchesDOM(\Core\Search\Lucene\Document\Html $doc, &$colorIndex);
	
	/**
	 * Highlight matches in $inputHTML
	 *
	 * @param string $inputHTML        	
	 * @return string
	 */
	public function highlightMatches($inputHTML) {
		$doc = \Core\Search\Lucene\Document\Html::loadHTML ( $inputHTML );
		
		$colorIndex = 0;
		$this->highlightMatchesDOM ( $doc, $colorIndex );
		
		return $doc->getHTML ();
	}
}

