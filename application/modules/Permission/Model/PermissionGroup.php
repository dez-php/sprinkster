<?php
namespace Permission;

use \Core\Base\MemcachedManager;
use \User\User;
use \Permission\UserToPermissionGroup;

class PermissionGroup extends \Base\Model\Reference {

	protected $_referenceMap = [
		'PermissionToUserGroup' => [
			'columns'                  => 'id',
			'refTableClass'            => 'Permission\PermissionToPermissionGroup',
			'refColumns'               => 'permission_group_id',
		],
	];

	/**
	 * Returns the permission group assigned to a user with the given ID
	 * @param  int $user_id The ID of the user to check for
	 * @return int          The ID of the assigned group or the ID of the default one if no assignment exists
	 */
	public static function getUserGroup($user_id)
	{
		$user_id = (int) $user_id;
		
		if(0 >= $user_id)
			return (int) \Base\Config::get('default_permission_group_id');

		$cache_key = MemcachedManager::key(__CLASS__, __METHOD__, $user_id);

		$userPermissionGroup = MemcachedManager::get($cache_key, function() use ($user_id) {
			$upgTable = new UserToPermissionGroup();
			return $upgTable->fetchRow($upgTable->makeWhere([ 'user_id' => $user_id ]));
		});

		$result = $userPermissionGroup && $userPermissionGroup->permission_group_id ? $userPermissionGroup->permission_group_id : \Base\Config::get('default_permission_group_id');

		return (int) $result;
	}

	/**
	 * This routine provides a check for users if they are in permission group
	 * @param  int  $user_id The ID of the user to check for
	 * @return bool          True or false, respectively whether the user has a group or not
	 */
	public static function hasUserGroup($user_id)
	{
		$user_id = (int) $user_id;
		
		if(0 >= $user_id)
			return NULL;

		$cache_key = MemcachedManager::key(__CLASS__, __METHOD__, $user_id);

		$userPermissionGroup = MemcachedManager::get($cache_key, function() use ($user_id) {
			$upgTable = new UserToPermissionGroup();
			$upgTable->fetchRow($upgTable->makeWhere([ 'user_id' => $user_id ]));
		});

		return $userPermissionGroup && $userPermissionGroup->permission_group_id;
	}

	/**
	 * Retrieve all groups in the system ordered by their names
	 * @return array An array with IDs and names of the permission groups in the system
	 */
	public static function getAll()
	{
		return \Core\Db\Init::getDefaultAdapter()->fetchAll('SELECT pg.id, pg.name FROM permission_group pg ORDER BY pg.name');
	}

}