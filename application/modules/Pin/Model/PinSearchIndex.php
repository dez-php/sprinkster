<?php

namespace Pin;

class PinSearchIndex extends \Base\Model\Reference {
	
	protected $_referenceMap    = array(
			'Search' => array(
					'columns'           => 'search_id',
					'refTableClass'     => 'Search\SearchIndex',
					'refColumns'        => 'id'
			),
	);

	
}