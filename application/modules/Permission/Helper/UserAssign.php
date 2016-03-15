<?php

namespace Permission\Helper;

class UserAssign {
	
	public function __construct($values)
	{
		if(!isset($values['__query_id']) || 0 >= (int) $values['__query_id'])
			return $this;

		$__query_id = (int) $values['__query_id'];

		if(!isset($values['permission_group_id']) || 0 >= (int) $values['permission_group_id'])
			return $this;

		$permission_group_id = (int) $values['permission_group_id'];
		
		$userPermissionGroup = new \Permission\UserToPermissionGroup();
		$userPermissionGroup->delete(array('user_id = ?' => $__query_id));
		
		$new = $userPermissionGroup->fetchNew();
		$new->user_id = $__query_id;
		$new->permission_group_id = $permission_group_id;

		\Permission\Permission::flush();
		
		$new->save();
	}
	
}