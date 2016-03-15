<?php

namespace Core\Search\Lucene\Index\SegmentWriter;

class StreamWriter extends \Core\Search\Lucene\Index\SegmentWriter {
	/**
	 * Object constructor.
	 *
	 * @param \Core\Search\Lucene\Storage\Directory $directory        	
	 * @param string $name        	
	 */
	public function __construct(\Core\Search\Lucene\Storage\Directory $directory, $name) {
		parent::__construct ( $directory, $name );
	}
	
	/**
	 * Create stored fields files and open them for write
	 */
	public function createStoredFieldsFiles() {
		$this->_fdxFile = $this->_directory->createFile ( $this->_name . '.fdx' );
		$this->_fdtFile = $this->_directory->createFile ( $this->_name . '.fdt' );
		
		$this->_files [] = $this->_name . '.fdx';
		$this->_files [] = $this->_name . '.fdt';
	}
	public function addNorm($fieldName, $normVector) {
		if (isset ( $this->_norms [$fieldName] )) {
			$this->_norms [$fieldName] .= $normVector;
		} else {
			$this->_norms [$fieldName] = $normVector;
		}
	}
	
	/**
	 * Close segment, write it to disk and return segment info
	 *
	 * @return \Core\Search\Lucene\Index\SegmentInfo
	 */
	public function close() {
		if ($this->_docCount == 0) {
			return null;
		}
		
		$this->_dumpFNM ();
		$this->_generateCFS ();
		
		return new \Core\Search\Lucene\Index\SegmentInfo ( $this->_directory, $this->_name, $this->_docCount, - 1, null, true, true );
	}
}

