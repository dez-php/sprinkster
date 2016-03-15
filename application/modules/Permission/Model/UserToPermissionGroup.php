<?php

namespace Permission;

class UserToPermissionGroup extends \Base\Model\Reference {

	protected $_referenceMap = [
		'User' => [
			'columns'            => 'user_id',
			'refTableClass'      => 'User\User',
			'refColumns'         => 'id',
		],

		'PermissionGroup' => [
			'columns'            => 'permission_group_id',
			'refTableClass'      => 'Permission\PermissionGroup',
			'refColumns'         => 'id',
		],
	];

	public function __construct($config = array())
	{
		parent::__construct($config);
	}

}