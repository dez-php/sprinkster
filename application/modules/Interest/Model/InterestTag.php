<?php

namespace Interest;

class InterestTag extends \Base\Model\Reference {
	
	protected $_referenceMap    = array(
		'Interest' => array(
			'columns'           => 'interest_id',
			'refTableClass'     => 'Interest\Interest',
			'refColumns'        => 'id'
		),
	);
	
}