<?php

namespace Core\Search\Lucene\Search\QueryEntry;

class Subquery extends \Core\Search\Lucene\Search\QueryEntry {
	/**
	 * Query
	 *
	 * @var \Core\Search\Lucene\Search\Query
	 */
	private $_query;
	
	/**
	 * Object constractor
	 *
	 * @param \Core\Search\Lucene\Search\Query $query        	
	 */
	public function __construct(\Core\Search\Lucene\Search\Query $query) {
		$this->_query = $query;
	}
	
	/**
	 * Process modifier ('~')
	 *
	 * @param mixed $parameter        	
	 * @throws \Core\Search\Lucene\Search\QueryParserException
	 */
	public function processFuzzyProximityModifier($parameter = null) {
		throw new \Core\Search\Lucene\Search\QueryParserException ( '\'~\' sign must follow term or phrase' );
	}
	
	/**
	 * Transform entry to a subquery
	 *
	 * @param string $encoding        	
	 * @return \Core\Search\Lucene\Search\Query
	 */
	public function getQuery($encoding) {
		$this->_query->setBoost ( $this->_boost );
		
		return $this->_query;
	}
}
