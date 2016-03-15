<?php
namespace Permission;

use \Core\Base\MemcachedManager;
use \User\User;

class Permission extends \Base\Model\Reference {

	protected $_referenceMap = [
		'PermissionToUserGroup' => [
			'columns'                  => 'id',
			'refTableClass'            => 'Permission\PermissionToPermissionGroup',
			'refColumns'               => 'permission_id',
		],
	];

	const PermissionModuleParts = 1;
	const PermissionControllerParts = 2;
	const PermissionActionParts = 3;
	
	const PermissionLevelNone = 0;
	const PermissionLevelModule = 1;
	const PermissionLevelController = 2;
	const PermissionLevelAction = 4;

	const PermissionCacheExpiration = 300;

	protected static $permissions = [];
	protected static $loaded = FALSE;

	public function __construct($config = array())
	{
		parent::__construct($config);
	}

	public static function load()
	{
		if(\Core\Session\Base::get('permissions_time') && self::PermissionCacheExpiration <= time() - \Core\Session\Base::get('permissions_time'))
		{
			self::$loaded = FALSE;
			self::flush();
		}

		if(self::$loaded)
			return;

		$permissions = \Core\Session\Base::get('permissions');
		
		if(!is_array($permissions) || empty($permissions))
			return;

		self::$permissions = array_merge(self::$permissions, $permissions);
	}

	public static function flush()
	{
		\Core\Session\Base::clear('permissions');
	}

	/**
	 * Retrieves a permission by code
	 * @param  string $code            Code of permission to search for
	 * @return Core\Db\Table\Row       The record of the permission
	 */
	public static function get($code)
	{
		//$cache_key = MemcachedManager::key(__CLASS__, __METHOD__, $code);

		//return MemcachedManager::get($cache_key, function() use ($code) {
			return (new self)->fetchRow([ 'code = ?' => $code ]);
		//});
	}

	/**
	 * Checks if a permission exists by its code
	 * @param  string $code The code of permission to check for
	 * @return bool         True if permission with the given code exists, false if not
	 */
	public static function exists($code)
	{
		return 0 < (new self)->countBy([ 'code' => $code ]);
	}

	/**
	 * Checks sum of permission by its code
	 * @param string $code  The code of permission to check for
	 * @return number Sum ot permission access
	 */
	public static function sumExists($code) {
		//$cache_key = MemcachedManager::key(__CLASS__, __METHOD__, $code);

		//return MemcachedManager::get($cache_key, function() use ($code) {
			$db = \Core\Db\Init::getDefaultAdapter();
			$code_parts = count(explode('.',$code));

			$colums = [
				'module' => ($code_parts > 0 ? 'SUM(IF(`code` = "' . self::getParts($code, self::PermissionModuleParts) . '",' . self::PermissionLevelModule . ',0))' : new \Core\Db\Expr('0') ),
				'controller' => ($code_parts > 1 ? 'SUM(IF(`code` = "' . self::getParts($code, self::PermissionControllerParts) . '", ' . self::PermissionLevelController . ',0))' : new \Core\Db\Expr('0') ),
				'action' => ($code_parts > 2 ? 'SUM(IF(`code` = "' . $code . '",' . self::PermissionLevelAction . ',0))' : new \Core\Db\Expr('0') )
			];
			
			return $db->fetchOne($db->select()->from((new self)->info('name'), implode(' | ', $colums)));
		//});
	}
	
	public static function getCode($code) {
		$permissionLevel = self::sumExists($code);
		
		if(self::PermissionLevelAction == (self::PermissionLevelAction & $permissionLevel))
			return $code;
		
		// No action-level permission defined with that code, getting controller-level permission
		if(self::PermissionLevelController == (self::PermissionLevelController & $permissionLevel))
			return self::getParts($code, self::PermissionControllerParts);
		
		// No controller-level permission getting for module-level one
		if(self::PermissionLevelModule == (self::PermissionLevelModule & $permissionLevel))
			return self::getParts($code, self::PermissionModuleParts);
		
		return TRUE;
	}
	
	/**
	 * Determines whether the current user has certain permission.
	 * In case the permission is not defined, it will search for controller and module-level permissions.
	 * @param  string $code The code of a permission
	 * @return bool         True if current user has the right or no permission is defined, false otherwise
	 */
	public static function capable($code)
	{
		self::load();

		if(isset(self::$permissions[$code]))
			return self::$permissions[$code];

		$user_id = User::getUserData()->id;
		$original = $code;

		/**
		 * NOTE: The functionality below is possible to be improved with single query
		 *       by selecting all possible parts and ordering them by code string length
		 *       to maintain the priority of checking. This however may make query heavier
		 *       when checks are minimal.
		 */
		
		$code = self::getCode($code);

		// No module level permission, assuming public access
		if(TRUE === $code)
		{
			self::$permissions[$original] = TRUE;
			return TRUE;
		}

		$db = \Core\Db\Init::getDefaultAdapter();

		$group_id = $user_id ? (int) PermissionGroup::getUserGroup($user_id) : \Base\Config::get('default_permission_group_id');
		$subscription_group_id = 0;

		if($user_id && \Install\Modules::isInstalled('subscription'))
		{
			$subscription = \Subscription\Subscription::current($user_id);

			if(is_object($subscription) && 0 < (int) $subscription->subscription_plan_id)
				if(($plan = \Subscription\SubscriptionPlan::get($subscription->subscription_plan_id)) && is_object($plan) && isset($plan->permission_group_id))
					$subscription_group_id = (int) $plan->permission_group_id;
		}
		
		//$cache_key = MemcachedManager::key(__CLASS__, __METHOD__, $group_id, $subscription_group_id, $code);

		//$count = MemcachedManager::get($cache_key, function() use ($db, $group_id, $subscription_group_id, $code) {
			return (int) $db->fetchOne('
				SELECT COUNT(*) count
				FROM permission p
				INNER JOIN permission_to_permission_group pg ON (p.id = pg.permission_id)
				INNER JOIN permission_group g ON (pg.permission_group_id = g.id)
				WHERE
					(g.id = ? OR g.id = ?)
				AND
					g.active = ?
				AND
					p.code = ?
				',

				[ (int) $group_id, (int) $subscription_group_id, TRUE, $code ]
			);
		//});

		self::$permissions[$original] = 0 < $count;

		if(!is_array(\Core\Session\Base::get('permissions')))
			\Core\Session\Base::set('permissions_time', time());

		\Core\Session\Base::set('permissions', array_unique( self::$permissions));

		return 0 < $count;
	}

	/**
	 * Retrieve all permissions in the system ordered by their names
	 * @return array An array with IDs and names of the permissions in the system
	 */
	public static function getAll()
	{
		return \Core\Db\Init::getDefaultAdapter()->fetchAll('SELECT p.id, p.name FROM permission p ORDER BY p.name');
	}

	/**
	 * Retrieve available permissions for a given group (these that are not enabled for it)
	 * @return array An array with IDs and names of the permissions
	 */
	public static function getAvailable($group_id)
	{
		$db = \Core\Db\Init::getDefaultAdapter();

		$enabled = $db->fetchCol('
			SELECT p.id
			FROM permission p
			INNER JOIN permission_to_permission_group pg ON (p.id = pg.permission_id)
			WHERE pg.permission_group_id = ?
			',

			[ (int) $group_id ]
		);

		array_walk($enabled, 'intval');

		if(empty($enabled))
			$enabled[] = 0;

		return $db->fetchAll('SELECT p.id, p.name FROM permission p WHERE p.id  NOT IN(' . implode(',', $enabled) . ') ORDER BY p.name');
	}

	/**
	 * Retrieve enabled permissions for a given group (these that are assigned to it)
	 * @return array An array with IDs and names of the permissions
	 */
	public static function getEnabled($group_id)
	{
		$db = \Core\Db\Init::getDefaultAdapter();

		return $db->fetchAll('
			SELECT p.id, p.name
			FROM permission p
			INNER JOIN permission_to_permission_group pg ON (p.id = pg.permission_id)
			WHERE pg.permission_group_id = ?
			ORDER BY p.name
			',

			[ (int) $group_id ]
		);
	}

	/**
	 * Returns permission code by part count
	 * @param  string $code  The actual permission
	 * @param  int    $count The count of parts to take from actual permission
	 * @return string        The reduced permission code or full code if shorter
	 */
	protected static function getParts($code, $count)
	{
		$count = (int) $count;

		if(0 >= $count)
			return $code;

		$parts = explode('.', $code);

		if(count($parts) <= $count)
			return $code;

		while(count($parts) > $count)
			array_pop($parts);

		return implode('.', $parts);
	}

	/**
	 * Creates new permission with the given code
	 * @param  string $code        The code of the new permission
	 * @param  string $name        The name of the new permission
	 * @param  string $description A description for the new permission, can be NULL
	 * @return mixed               Newly assigned ID of the registered permission, FALSE or NULL otherwise
	 */
	public static function register($code, $name = '', $description = NULL)
	{
		if(self::get($code))
			return TRUE;

		(new self)->delete([ 'code = ?' => $code ]);

		$new = (new self)->fetchNew();
		$new->name = $name;
		$new->code = $code;
		$new->description = $description;

		return $new->save();
	}

	/**
	 * Unregisters existing permission from the system
	 * @param  string $code The code of the permission to remove
	 * @return void         
	 */
	public static function unregister($code)
	{
		$context = new self;
		$context->delete($context->makeWhere([ 'code' => $code ]));
	}
}