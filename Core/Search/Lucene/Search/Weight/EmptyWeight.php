<?php

namespace Core\Search\Lucene\Search\Weight;

class EmptyWeight extends \Core\Search\Lucene\Search\Weight {
	/**
	 * The sum of squared weights of contained query clauses.
	 *
	 * @return float
	 */
	public function sumOfSquaredWeights() {
		return 1;
	}
	
	/**
	 * Assigns the query normalization factor to this.
	 *
	 * @param float $queryNorm        	
	 */
	public function normalize($queryNorm) {
	}
}

