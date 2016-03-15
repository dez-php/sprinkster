<?php

namespace Core\Search\Lucene;

class Document {
	
	/**
	 * Associative array \Core\Search\Lucene\Field objects where the keys to the
	 * array are the names of the fields.
	 *
	 * @var array
	 */
	protected $_fields = array ();
	
	/**
	 * Field boost factor
	 * It's not stored directly in the index, but affects on normalization
	 * factor
	 *
	 * @var float
	 */
	public $boost = 1.0;
	
	/**
	 * Proxy method for getFieldValue(), provides more convenient access to
	 * the string value of a field.
	 *
	 * @param
	 *        	$offset
	 * @return string
	 */
	public function __get($offset) {
		return $this->getFieldValue ( $offset );
	}
	
	/**
	 * Add a field object to this document.
	 *
	 * @param \Core\Search\Lucene\Field $field        	
	 * @return \Core\Search\Lucene\Document
	 */
	public function addField(\Core\Search\Lucene\Field $field) {
		$this->_fields [$field->name] = $field;
		
		return $this;
	}
	
	/**
	 * Return an array with the names of the fields in this document.
	 *
	 * @return array
	 */
	public function getFieldNames() {
		return array_keys ( $this->_fields );
	}
	
	/**
	 * Returns \Core\Search\Lucene\Field object for a named field in this
	 * document.
	 *
	 * @param string $fieldName        	
	 * @return \Core\Search\Lucene\Field
	 */
	public function getField($fieldName) {
		if (! array_key_exists ( $fieldName, $this->_fields )) {
			throw new \Core\Search\Lucene\Exception ( "Field name \"$fieldName\" not found in document." );
		}
		return $this->_fields [$fieldName];
	}
	
	/**
	 * Returns the string value of a named field in this document.
	 *
	 * @see __get()
	 * @return string
	 */
	public function getFieldValue($fieldName) {
		return $this->getField ( $fieldName )->value;
	}
	
	/**
	 * Returns the string value of a named field in UTF-8 encoding.
	 *
	 * @see __get()
	 * @return string
	 */
	public function getFieldUtf8Value($fieldName) {
		return $this->getField ( $fieldName )->getUtf8Value ();
	}
}
