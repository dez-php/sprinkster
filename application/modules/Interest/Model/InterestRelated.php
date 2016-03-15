<?php

namespace Interest;

class InterestRelated extends \Base\Model\Reference {
	
	protected $_referenceMap    = array(
		'Interest' => array(
			'columns'           => 'interest_id',
			'refTableClass'     => 'Interest\Interest',
			'refColumns'        => 'id'
		),
		'Self' => array(
			'columns'           => 'related_id',
			'refTableClass'     => 'Interest\Interest',
			'refColumns'        => 'id'
		),
		'Tag' => array(
			'columns'           => 'interest_id',
			'refTableClass'     => 'Interest\InterestTag',
			'refColumns'        => 'interest_id'
		),
	);
	
}