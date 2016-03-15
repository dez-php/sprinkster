<?php

namespace Interest;

class InterestFollow extends \Base\Model\Reference {


	protected $_referenceMap    = array(
		'Interest' => array(
			'columns'           => 'interest_id',
			'refTableClass'     => 'Interest\Interest',
			'refColumns'        => 'id'
		),
		'User' => array(
			'columns'           => 'user_id',
			'refTableClass'     => 'User\User',
			'refColumns'        => 'id'
		)
	);
	
	public static function interestFollowing($user_id) {
		$self = new self();
		return $self->select()->from($self,'interest_id')->where('user_id = ?', $user_id);
	}
	
}