<?php

namespace User;

class UserFollowDefault extends \Base\Model\Reference {
	
	protected $_referenceMap    = array(
			'User' => array(
					'columns'           => 'user_id',
					'refTableClass'     => 'User\User',
					'refColumns'        => 'id'
			),
	);
	
	//virtual map for reference
	protected $_referenceReverseMap    = array(
			'User\User' => array(
					'columns'           => 'id',
					'refTableClass'     => 'User\UserFollowDefault',
					'refColumns'        => 'user_id',
					'singleRow'			=> true
			),
	);
	
}