<?php

namespace Core\Search\Lucene\Index;

class TermInfo {
	/**
	 * The number of documents which contain the term.
	 *
	 * @var integer
	 */
	public $docFreq;
	
	/**
	 * Data offset in a Frequencies file.
	 *
	 * @var integer
	 */
	public $freqPointer;
	
	/**
	 * Data offset in a Positions file.
	 *
	 * @var integer
	 */
	public $proxPointer;
	
	/**
	 * ScipData offset in a Frequencies file.
	 *
	 * @var integer
	 */
	public $skipOffset;
	
	/**
	 * Term offset of the _next_ term in a TermDictionary file.
	 * Used only for Term Index
	 *
	 * @var integer
	 */
	public $indexPointer;
	public function __construct($docFreq, $freqPointer, $proxPointer, $skipOffset, $indexPointer = null) {
		$this->docFreq = $docFreq;
		$this->freqPointer = $freqPointer;
		$this->proxPointer = $proxPointer;
		$this->skipOffset = $skipOffset;
		$this->indexPointer = $indexPointer;
	}
}

