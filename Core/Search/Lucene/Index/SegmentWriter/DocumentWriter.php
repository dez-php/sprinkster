<?php

namespace Core\Search\Lucene\Index\SegmentWriter;

class DocumentWriter extends \Core\Search\Lucene\Index\SegmentWriter {
	/**
	 * Term Dictionary
	 * Array of the \Core\Search\Lucene\Index\Term objects
	 * Corresponding \Core\Search\Lucene\Index\TermInfo object stored in the
	 * $_termDictionaryInfos
	 *
	 * @var array
	 */
	protected $_termDictionary;
	
	/**
	 * Documents, which contain the term
	 *
	 * @var array
	 */
	protected $_termDocs;
	
	/**
	 * Object constructor.
	 *
	 * @param \Core\Search\Lucene\Storage\Directory $directory        	
	 * @param string $name        	
	 */
	public function __construct(\Core\Search\Lucene\Storage\Directory $directory, $name) {
		parent::__construct ( $directory, $name );
		
		$this->_termDocs = array ();
		$this->_termDictionary = array ();
	}
	
	/**
	 * Adds a document to this segment.
	 *
	 * @param \Core\Search\Lucene\Document $document        	
	 * @throws \Core\Search\Lucene\Exception
	 */
	public function addDocument(\Core\Search\Lucene\Document $document) {
		$storedFields = array ();
		$docNorms = array ();
		$similarity = \Core\Search\Lucene\Search\Similarity::getDefault ();
		
		foreach ( $document->getFieldNames () as $fieldName ) {
			$field = $document->getField ( $fieldName );
			$this->addField ( $field );
			
			if ($field->storeTermVector) {
				/**
				 *
				 * @todo term vector storing support
				 */
				throw new \Core\Search\Lucene\Exception ( 'Store term vector functionality is not supported yet.' );
			}
			
			if ($field->isIndexed) {
				if ($field->isTokenized) {
					$analyzer = \Core\Search\Lucene\Analysis\Analyzer::getDefault ();
					$analyzer->setInput ( $field->value, $field->encoding );
					
					$position = 0;
					$tokenCounter = 0;
					while ( ($token = $analyzer->nextToken ()) !== null ) {
						$tokenCounter ++;
						
						$term = new \Core\Search\Lucene\Index\Term ( $token->getTermText (), $field->name );
						$termKey = $term->key ();
						
						if (! isset ( $this->_termDictionary [$termKey] )) {
							// New term
							$this->_termDictionary [$termKey] = $term;
							$this->_termDocs [$termKey] = array ();
							$this->_termDocs [$termKey] [$this->_docCount] = array ();
						} else if (! isset ( $this->_termDocs [$termKey] [$this->_docCount] )) {
							// Existing term, but new term entry
							$this->_termDocs [$termKey] [$this->_docCount] = array ();
						}
						$position += $token->getPositionIncrement ();
						$this->_termDocs [$termKey] [$this->_docCount] [] = $position;
					}
					
					$docNorms [$field->name] = chr ( $similarity->encodeNorm ( $similarity->lengthNorm ( $field->name, $tokenCounter ) * $document->boost * $field->boost ) );
				} else {
					$term = new \Core\Search\Lucene\Index\Term ( $field->getUtf8Value (), $field->name );
					$termKey = $term->key ();
					
					if (! isset ( $this->_termDictionary [$termKey] )) {
						// New term
						$this->_termDictionary [$termKey] = $term;
						$this->_termDocs [$termKey] = array ();
						$this->_termDocs [$termKey] [$this->_docCount] = array ();
					} else if (! isset ( $this->_termDocs [$termKey] [$this->_docCount] )) {
						// Existing term, but new term entry
						$this->_termDocs [$termKey] [$this->_docCount] = array ();
					}
					$this->_termDocs [$termKey] [$this->_docCount] [] = 0; // position
					
					$docNorms [$field->name] = chr ( $similarity->encodeNorm ( $similarity->lengthNorm ( $field->name, 1 ) * $document->boost * $field->boost ) );
				}
			}
			
			if ($field->isStored) {
				$storedFields [] = $field;
			}
		}
		
		foreach ( $this->_fields as $fieldName => $field ) {
			if (! $field->isIndexed) {
				continue;
			}
			
			if (! isset ( $this->_norms [$fieldName] )) {
				$this->_norms [$fieldName] = str_repeat ( chr ( $similarity->encodeNorm ( $similarity->lengthNorm ( $fieldName, 0 ) ) ), $this->_docCount );
			}
			
			if (isset ( $docNorms [$fieldName] )) {
				$this->_norms [$fieldName] .= $docNorms [$fieldName];
			} else {
				$this->_norms [$fieldName] .= chr ( $similarity->encodeNorm ( $similarity->lengthNorm ( $fieldName, 0 ) ) );
			}
		}
		
		$this->addStoredFields ( $storedFields );
	}
	
	/**
	 * Dump Term Dictionary (.tis) and Term Dictionary Index (.tii) segment
	 * files
	 */
	protected function _dumpDictionary() {
		ksort ( $this->_termDictionary, SORT_STRING );
		
		$this->initializeDictionaryFiles ();
		
		foreach ( $this->_termDictionary as $termId => $term ) {
			$this->addTerm ( $term, $this->_termDocs [$termId] );
		}
		
		$this->closeDictionaryFiles ();
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
		$this->_dumpDictionary ();
		
		$this->_generateCFS ();
		
		return new \Core\Search\Lucene\Index\SegmentInfo ( $this->_directory, $this->_name, $this->_docCount, - 1, null, true, true );
	}
}

