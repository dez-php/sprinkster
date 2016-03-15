<?php

namespace Core\Search\Lucene\Index;

class FieldInfo {
	public $name;
	public $isIndexed;
	public $number;
	public $storeTermVector;
	public function __construct($name, $isIndexed, $number, $storeTermVector) {
		$this->name = $name;
		$this->isIndexed = $isIndexed;
		$this->number = $number;
		$this->storeTermVector = $storeTermVector;
	}
}

