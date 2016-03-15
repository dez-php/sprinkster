<?php

namespace Meta;

class MetaDescription extends \Base\Model\Reference {

	protected $_referenceMap    = array(
			'Meta' => array(
					'columns'           => 'meta_id',
					'refTableClass'     => 'Meta\Meta',
					'refColumns'        => 'id',
					'singleRow'			=> true
			),
	);
	
}