<?php

namespace Core\Dom\Query;

class Result implements \Iterator,\Countable {
	/**
	 * Number of results
	 * 
	 * @var int
	 */
	protected $_count;
	
	/**
	 * CSS Selector query
	 * 
	 * @var string
	 */
	protected $_cssQuery;
	
	/**
	 *
	 * @var \DOMDocument
	 */
	protected $_document;
	
	/**
	 *
	 * @var DOMNodeList
	 */
	protected $_nodeList;
	
	/**
	 * Current iterator position
	 * 
	 * @var int
	 */
	protected $_position = 0;
	
	/**
	 *
	 * @var DOMXPath
	 */
	protected $_xpath;
	
	/**
	 * XPath query
	 * 
	 * @var string
	 */
	protected $_xpathQuery;
	
	/**
	 * Constructor
	 *
	 * @param string $cssQuery        	
	 * @param string|array $xpathQuery        	
	 * @param DOMDocument $document        	
	 * @param DOMNodeList $nodeList        	
	 * @return void
	 */
	public function __construct($cssQuery, $xpathQuery,\DOMDocument $document,\DOMNodeList $nodeList) {
		$this->_cssQuery = $cssQuery;
		$this->_xpathQuery = $xpathQuery;
		$this->_document = $document;
		$this->_nodeList = $nodeList;
	}
	
	/**
	 * Retrieve CSS Query
	 *
	 * @return string
	 */
	public function getCssQuery() {
		return $this->_cssQuery;
	}
	
	/**
	 * Retrieve XPath query
	 *
	 * @return string
	 */
	public function getXpathQuery() {
		return $this->_xpathQuery;
	}
	
	/**
	 * Retrieve DOMDocument
	 *
	 * @return DOMDocument
	 */
	public function getDocument() {
		return $this->_document;
	}
	
	/**
	 * Iterator: rewind to first element
	 *
	 * @return void
	 */
	public function rewind() {
		$this->_position = 0;
		return $this->_nodeList->item ( 0 );
	}
	
	/**
	 * Iterator: is current position valid?
	 *
	 * @return bool
	 */
	public function valid() {
		if (in_array ( $this->_position, range ( 0, $this->_nodeList->length - 1 ) ) && $this->_nodeList->length > 0) {
			return true;
		}
		return false;
	}
	
	/**
	 * Iterator: return current element
	 *
	 * @return \DOMElement
	 */
	public function current() {
		return $this->_nodeList->item ( $this->_position );
	}
	
	/**
	 * Iterator: return key of current element
	 *
	 * @return int
	 */
	public function key() {
		return $this->_position;
	}
	
	/**
	 * Iterator: move to next element
	 *
	 * @return void
	 */
	public function next() {
		++ $this->_position;
		return $this->_nodeList->item ( $this->_position );
	}
	
	/**
	 * Iterator: get element
	 *
	 * @return \DOMElement
	 */
	public function getElement($id) {
		return $this->_nodeList->item ( $id );
	}
	
	/**
	 * @param int $id
	 * @return \Core\Dom\Query
	 */
	public function get($id) {
		$innerHTML = "";
		$element = $this->_nodeList->item ( $id );
		if($element) {
			$children  = $element->childNodes;
			foreach ($children as $child) {
				$innerHTML .= $element->ownerDocument->saveHTML($child);
			}
		}
		return new \Core\Dom\Query(trim($innerHTML), $this->_document->encoding);
	}

	/**
	 * Perform a CSS selector query
	 *
	 * @param string $query
	 * @return \Core\Dom\Query\Result
	 */
	public function query($query) {
		$dom = new \Core\Dom\Query(trim($this->innerHTML()), $this->_document->encoding);
		return $dom->query($query);
	}
	
	/**
	 * Countable: get count
	 *
	 * @return int
	 */
	public function count() {
		return $this->_nodeList->length;
	}
	
	public function innerHTML() {
		$innerHTML = "";
		$element = $this->current();
		if($element) {
			$children  = $this->current()->childNodes;
			foreach ($children as $child) {
				$innerHTML .= $element->ownerDocument->saveHTML($child);
			}
		}
		return $innerHTML;
	}

	/**
	 * @param string $tag
	 * @return \DOMElement|bool
	 */
	public function getParentByTag($tag, $element = null) {
		$element = $element ? $element : $this->current();
		if(isset($element->parentNode)) {
			$obj_parent = $element->parentNode;
			if(!$obj_parent) { return false; }
			if(strtolower($obj_parent->nodeName) == strtolower($tag)) {
				return $obj_parent;
			} else {
				return $this->getParentByTag($tag, $obj_parent);
			}
		}
		return false;
	}
}
