<?php

namespace Core\Search\Lucene\Index;

class SegmentMerger {
	/**
	 * Target segment writer
	 *
	 * @var \Core\Search\Lucene\Index\SegmentWriter_StreamWriter
	 */
	private $_writer;
	
	/**
	 * Number of docs in a new segment
	 *
	 * @var integer
	 */
	private $_docCount;
	
	/**
	 * A set of segments to be merged
	 *
	 * @var array \Core\Search\Lucene\Index\SegmentInfo
	 */
	private $_segmentInfos = array ();
	
	/**
	 * Flag to signal, that merge is already done
	 *
	 * @var boolean
	 */
	private $_mergeDone = false;
	
	/**
	 * Field map
	 * [<segment_name>][<field_number>] => <target_field_number>
	 *
	 * @var array
	 */
	private $_fieldsMap = array ();
	
	/**
	 * Object constructor.
	 *
	 * Creates new segment merger with $directory as target to merge segments
	 * into
	 * and $name as a name of new segment
	 *
	 * @param \Core\Search\Lucene\Storage\Directory $directory        	
	 * @param string $name        	
	 */
	public function __construct($directory, $name) {
		$this->_writer = new \Core\Search\Lucene\Index\SegmentWriter\StreamWriter ( $directory, $name );
	}
	
	/**
	 * Add segmnet to a collection of segments to be merged
	 *
	 * @param \Core\Search\Lucene\Index\SegmentInfo $segment        	
	 */
	public function addSource(\Core\Search\Lucene\Index\SegmentInfo $segmentInfo) {
		$this->_segmentInfos [$segmentInfo->getName ()] = $segmentInfo;
	}
	
	/**
	 * Do merge.
	 *
	 * Returns number of documents in newly created segment
	 *
	 * @return \Core\Search\Lucene\Index\SegmentInfo
	 * @throws \Core\Search\Lucene\Exception
	 */
	public function merge() {
		if ($this->_mergeDone) {
			throw new \Core\Search\Lucene\Exception ( 'Merge is already done.' );
		}
		
		if (count ( $this->_segmentInfos ) < 1) {
			throw new \Core\Search\Lucene\Exception ( 'Wrong number of segments to be merged (' . count ( $this->_segmentInfos ) . ').' );
		}
		
		$this->_mergeFields ();
		$this->_mergeNorms ();
		$this->_mergeStoredFields ();
		$this->_mergeTerms ();
		
		$this->_mergeDone = true;
		
		return $this->_writer->close ();
	}
	
	/**
	 * Merge fields information
	 */
	private function _mergeFields() {
		foreach ( $this->_segmentInfos as $segName => $segmentInfo ) {
			foreach ( $segmentInfo->getFieldInfos () as $fieldInfo ) {
				$this->_fieldsMap [$segName] [$fieldInfo->number] = $this->_writer->addFieldInfo ( $fieldInfo );
			}
		}
	}
	
	/**
	 * Merge field's normalization factors
	 */
	private function _mergeNorms() {
		foreach ( $this->_writer->getFieldInfos () as $fieldInfo ) {
			if ($fieldInfo->isIndexed) {
				foreach ( $this->_segmentInfos as $segName => $segmentInfo ) {
					if ($segmentInfo->hasDeletions ()) {
						$srcNorm = $segmentInfo->normVector ( $fieldInfo->name );
						$norm = '';
						$docs = $segmentInfo->count ();
						for($count = 0; $count < $docs; $count ++) {
							if (! $segmentInfo->isDeleted ( $count )) {
								$norm .= $srcNorm [$count];
							}
						}
						$this->_writer->addNorm ( $fieldInfo->name, $norm );
					} else {
						$this->_writer->addNorm ( $fieldInfo->name, $segmentInfo->normVector ( $fieldInfo->name ) );
					}
				}
			}
		}
	}
	
	/**
	 * Merge fields information
	 */
	private function _mergeStoredFields() {
		$this->_docCount = 0;
		
		foreach ( $this->_segmentInfos as $segName => $segmentInfo ) {
			$fdtFile = $segmentInfo->openCompoundFile ( '.fdt' );
			
			for($count = 0; $count < $segmentInfo->count (); $count ++) {
				$fieldCount = $fdtFile->readVInt ();
				$storedFields = array ();
				
				for($count2 = 0; $count2 < $fieldCount; $count2 ++) {
					$fieldNum = $fdtFile->readVInt ();
					$bits = $fdtFile->readByte ();
					$fieldInfo = $segmentInfo->getField ( $fieldNum );
					
					if (! ($bits & 2)) { // Text data
						$storedFields [] = new \Core\Search\Lucene\Field ( $fieldInfo->name, $fdtFile->readString (), 'UTF-8', true, $fieldInfo->isIndexed, $bits & 1 );
					} else { // Binary data
						$storedFields [] = new \Core\Search\Lucene\Field ( $fieldInfo->name, $fdtFile->readBinary (), '', true, $fieldInfo->isIndexed, $bits & 1, true );
					}
				}
				
				if (! $segmentInfo->isDeleted ( $count )) {
					$this->_docCount ++;
					$this->_writer->addStoredFields ( $storedFields );
				}
			}
		}
	}
	
	/**
	 * Merge fields information
	 */
	private function _mergeTerms() {
		$segmentInfoQueue = new \Core\Search\Lucene\Index\SegmentInfoPriorityQueue ();
		
		$segmentStartId = 0;
		foreach ( $this->_segmentInfos as $segName => $segmentInfo ) {
			$segmentStartId = $segmentInfo->reset ( $segmentStartId, \Core\Search\Lucene\Index\SegmentInfo::SM_MERGE_INFO );
			
			// Skip "empty" segments
			if ($segmentInfo->currentTerm () !== null) {
				$segmentInfoQueue->put ( $segmentInfo );
			}
		}
		
		$this->_writer->initializeDictionaryFiles ();
		
		$termDocs = array ();
		while ( ($segmentInfo = $segmentInfoQueue->pop ()) !== null ) {
			// Merge positions array
			$termDocs += $segmentInfo->currentTermPositions ();
			
			if ($segmentInfoQueue->top () === null || $segmentInfoQueue->top ()->currentTerm ()->key () != $segmentInfo->currentTerm ()->key ()) {
				// We got new term
				ksort ( $termDocs, SORT_NUMERIC );
				
				// Add term if it's contained in any document
				if (count ( $termDocs ) > 0) {
					$this->_writer->addTerm ( $segmentInfo->currentTerm (), $termDocs );
				}
				$termDocs = array ();
			}
			
			$segmentInfo->nextTerm ();
			// check, if segment dictionary is finished
			if ($segmentInfo->currentTerm () !== null) {
				// Put segment back into the priority queue
				$segmentInfoQueue->put ( $segmentInfo );
			}
		}
		
		$this->_writer->closeDictionaryFiles ();
	}
}
