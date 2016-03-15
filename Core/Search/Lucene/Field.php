<?php

namespace Core\Search\Lucene;

class Field {
	/**
	 * Field name
	 *
	 * @var string
	 */
	public $name;
	
	/**
	 * Field value
	 *
	 * @var boolean
	 */
	public $value;
	
	/**
	 * Field is to be stored in the index for return with search hits.
	 *
	 * @var boolean
	 */
	public $isStored = false;
	
	/**
	 * Field is to be indexed, so that it may be searched on.
	 *
	 * @var boolean
	 */
	public $isIndexed = true;
	
	/**
	 * Field should be tokenized as text prior to indexing.
	 *
	 * @var boolean
	 */
	public $isTokenized = true;
	/**
	 * Field is stored as binary.
	 *
	 * @var boolean
	 */
	public $isBinary = false;
	
	/**
	 * Field are stored as a term vector
	 *
	 * @var boolean
	 */
	public $storeTermVector = false;
	
	/**
	 * Field boost factor
	 * It's not stored directly in the index, but affects on normalization
	 * factor
	 *
	 * @var float
	 */
	public $boost = 1.0;
	
	/**
	 * Field value encoding.
	 *
	 * @var string
	 */
	public $encoding;
	
	/**
	 * Object constructor
	 *
	 * @param string $name        	
	 * @param string $value        	
	 * @param string $encoding        	
	 * @param boolean $isStored        	
	 * @param boolean $isIndexed        	
	 * @param boolean $isTokenized        	
	 * @param boolean $isBinary        	
	 */
	public function __construct($name, $value, $encoding, $isStored, $isIndexed, $isTokenized, $isBinary = false) {
		$this->name = $name;
		$this->value = $value;
		
		if (! $isBinary) {
			$this->encoding = $encoding;
			$this->isTokenized = $isTokenized;
		} else {
			$this->encoding = '';
			$this->isTokenized = false;
		}
		
		$this->isStored = $isStored;
		$this->isIndexed = $isIndexed;
		$this->isBinary = $isBinary;
		
		$this->storeTermVector = false;
		$this->boost = 1.0;
	}
	
	/**
	 * Constructs a String-valued Field that is not tokenized, but is indexed
	 * and stored.
	 * Useful for non-text fields, e.g. date or url.
	 *
	 * @param string $name        	
	 * @param string $value        	
	 * @param string $encoding        	
	 * @return \Core\Search\Lucene\Field
	 */
	public static function keyword($name, $value, $encoding = '') {
		return new self ( $name, $value, $encoding, true, true, false );
	}
	
	/**
	 * Constructs a String-valued Field that is not tokenized nor indexed,
	 * but is stored in the index, for return with hits.
	 *
	 * @param string $name        	
	 * @param string $value        	
	 * @param string $encoding        	
	 * @return \Core\Search\Lucene\Field
	 */
	public static function unIndexed($name, $value, $encoding = '') {
		return new self ( $name, $value, $encoding, true, false, false );
	}
	
	/**
	 * Constructs a Binary String valued Field that is not tokenized nor
	 * indexed,
	 * but is stored in the index, for return with hits.
	 *
	 * @param string $name        	
	 * @param string $value        	
	 * @param string $encoding        	
	 * @return \Core\Search\Lucene\Field
	 */
	public static function binary($name, $value) {
		return new self ( $name, $value, '', true, false, false, true );
	}
	
	/**
	 * Constructs a String-valued Field that is tokenized and indexed,
	 * and is stored in the index, for return with hits.
	 * Useful for short text
	 * fields, like "title" or "subject". Term vector will not be stored for
	 * this field.
	 *
	 * @param string $name        	
	 * @param string $value        	
	 * @param string $encoding        	
	 * @return \Core\Search\Lucene\Field
	 */
	public static function text($name, $value, $encoding = '') {
		return new self ( $name, $value, $encoding, true, true, true );
	}
	
	/**
	 * Constructs a String-valued Field that is tokenized and indexed,
	 * but that is not stored in the index.
	 *
	 * @param string $name        	
	 * @param string $value        	
	 * @param string $encoding        	
	 * @return \Core\Search\Lucene\Field
	 */
	public static function unStored($name, $value, $encoding = '') {
		return new self ( $name, $value, $encoding, false, true, true );
	}
	
	/**
	 * Get field value in UTF-8 encoding
	 *
	 * @return string
	 */
	public function getUtf8Value() {
		if (strcasecmp ( $this->encoding, 'utf8' ) == 0 || strcasecmp ( $this->encoding, 'utf-8' ) == 0) {
			return $this->value;
		} else {
			return mb_convert_encoding ( $this->value, 'UTF-8', mb_detect_encoding ( $this->value ) );
		}
	}
}

