<?php

namespace Core\Search\Lucene\Search;

abstract class Weight {
	/**
	 * Normalization factor.
	 * This value is stored only for query expanation purpose and not used in
	 * any other place
	 *
	 * @var float
	 */
	protected $_queryNorm;
	
	/**
	 * Weight value
	 *
	 * Weight value may be initialized in sumOfSquaredWeights() or normalize()
	 * because they both are invoked either in Query::_initWeight (for top-level
	 * query) or
	 * in corresponding methods of parent query's weights
	 *
	 * @var float
	 */
	protected $_value;
	
	/**
	 * The weight for this query.
	 *
	 * @return float
	 */
	public function getValue() {
		return $this->_value;
	}
	
	/**
	 * The sum of squared weights of contained query clauses.
	 *
	 * @return float
	 */
	abstract public function sumOfSquaredWeights();
	
	/**
	 * Assigns the query normalization factor to this.
	 *
	 * @param
	 *        	$norm
	 */
	abstract public function normalize($norm);
}

