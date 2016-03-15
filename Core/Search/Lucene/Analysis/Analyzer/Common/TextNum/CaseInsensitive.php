<?php

namespace Core\Search\Lucene\Analysis\Analyzer\Common\TextNum;

class CaseInsensitive extends \Core\Search\Lucene\Analysis\Analyzer\Common\TextNum {
	public function __construct() {
		$this->addFilter ( new \Core\Search\Lucene\Analysis\TokenFilter\LowerCase () );
	}
}

