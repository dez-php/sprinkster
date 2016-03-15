<?php

namespace Core\Search\Lucene\Search;

class QueryHit {
	/**
	 * Object handle of the index
	 * 
	 * @var \Core\Search\Lucene\InterfaceLucene
	 */
	protected $_index = null;
	
	/**
	 * Object handle of the document associated with this hit
	 * 
	 * @var \Core\Search\Lucene\Document
	 */
	protected $_document = null;
	
	/**
	 * Number of the document in the index
	 * 
	 * @var integer
	 */
	public $id;
	
	/**
	 * Score of the hit
	 * 
	 * @var float
	 */
	public $score;
	
	/**
	 * Constructor - pass object handle of \Core\Search\Lucene\InterfaceLucene
	 * index that produced
	 * the hit so the document can be retrieved easily from the hit.
	 *
	 * @param \Core\Search\Lucene\InterfaceLucene $index        	
	 */
	public function __construct(\Core\Search\Lucene\InterfaceLucene $index) {
		$this->_index = new \Core\Search\Lucene\Proxy ( $index );
	}
	
	/**
	 * Convenience function for getting fields from the document
	 * associated with this hit.
	 *
	 * @param string $offset        	
	 * @return string
	 */
	public function __get($offset) {
		return $this->getDocument ()->getFieldValue ( $offset );
	}
	
	/**
	 * Return the document object for this hit
	 *
	 * @return \Core\Search\Lucene\Document
	 */
	public function getDocument() {
		if (! $this->_document instanceof \Core\Search\Lucene\Document) {
			$this->_document = $this->_index->getDocument ( $this->id );
		}
		
		return $this->_document;
	}
	
	/**
	 * Return the index object for this hit
	 *
	 * @return \Core\Search\Lucene\InterfaceLucene
	 */
	public function getIndex() {
		return $this->_index;
	}
}

