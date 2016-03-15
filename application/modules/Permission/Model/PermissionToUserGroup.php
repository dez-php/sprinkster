<?php

namespace Permission;

class PermissionToUserGroup extends \Base\Model\Reference {

	protected $_referenceMap = [
		'Permission' => [
			'columns'            => 'permission_id',
			'refTableClass'      => 'Permission\Permission',
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