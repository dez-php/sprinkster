<?php

namespace Core\Search\Lucene\Analysis\Analyzer\Common\Text;

class CaseInsensitive extends \Core\Search\Lucene\Analysis\Analyzer\Common\Text {
	public function __construct() {
		$this->addFilter ( new \Core\Search\Lucene\Analysis\TokenFilter\LowerCase () );
	}
}

