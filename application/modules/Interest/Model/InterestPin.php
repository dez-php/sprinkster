<?php

namespace Interest;

class InterestPin extends \Base\Model\Reference {
	
	protected $_referenceMap    = array(
		'Interest' => array(
			'columns'           => 'interest_id',
			'refTableClass'     => 'Interest\Interest',
			'refColumns'        => 'id'
		),
		'Pin' => array(
			'columns'           => 'pin_id',
			'refTableClass'     => 'Pin\Pin',
			'refColumns'        => 'id'
		)
	);
	
	public static function getPins($interest_id) {
		$self = new self();
		return $self->select()->from($self,'pin_id')->where('interest_id = ?', $interest_id);
	}
	
}