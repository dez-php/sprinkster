<?php

namespace Core\Search\Lucene\Index;

class SegmentInfoPriorityQueue extends \Core\Search\Lucene\PriorityQueue {
	/**
	 * Compare elements
	 *
	 * Returns true, if $el1 is less than $el2; else otherwise
	 *
	 * @param mixed $segmentInfo1        	
	 * @param mixed $segmentInfo2        	
	 * @return boolean
	 */
	protected function _less($segmentInfo1, $segmentInfo2) {
		return strcmp ( $segmentInfo1->currentTerm ()->key (), $segmentInfo2->currentTerm ()->key () ) < 0;
	}
}
