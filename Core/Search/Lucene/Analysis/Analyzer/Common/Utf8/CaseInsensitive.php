<?php

namespace Core\Search\Lucene\Analysis\Analyzer\Common\Utf8;

class CaseInsensitive extends \Core\Search\Lucene\Analysis\Analyzer\Common\Utf8 {
	public function __construct() {
		parent::__construct ();
		
		$this->addFilter ( new \Core\Search\Lucene\Analysis\TokenFilter\LowerCaseUtf8 () );
	}
}

