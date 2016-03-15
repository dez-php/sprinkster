<?php

namespace Core\Acl\Role;

class Registry
{
    /**
     * Internal Role registry data storage
     *
     * @var array
     */
    protected $_roles = array();

    /**
     * Adds a Role having an identifier unique to the registry
     *
     * The $parents parameter may be a reference to, or the string identifier for,
     * a Role existing in the registry, or $parents may be passed as an array of
     * these - mixing string identifiers and objects is ok - to indicate the Roles
     * from which the newly added Role will directly inherit.
     *
     * In order to resolve potential ambiguities with conflicting rules inherited
     * from different parents, the most recently added parent takes precedence over
     * parents that were previously added. In other words, the first parent added
     * will have the least priority, and the last parent added will have the
     * highest priority.
     *
     * @param  \Core\Acl\Role\RoleInterface              $role
     * @param  \Core\Acl\Role\RoleInterface|string|array $parents
     * @throws \Core\Acl\Role\Registry\Exception
     * @return \Core\Acl\Role\Registry Provides a fluent interface
     */
    public function add(\Core\Acl\Role\RoleInterface $role, $parents = null)
    {
        $roleId = $role->getRoleId();

        if ($this->has($roleId)) {
            /**
             * @see \Core\Acl\Role\Registry\Exception
             */
            require_once 'Core/Acl/Role/Registry/Exception.php';
            throw new \Core\Acl\Role\Registry\Exception("Role id '$roleId' already exists in the registry");
        }

        $roleParents = array();

        if (null !== $parents) {
            if (!is_array($parents)) {
                $parents = array($parents);
            }
            /**
             * @see \Core\Acl\Role\Registry\Exception
             */
            require_once 'Core/Acl/Role/Registry/Exception.php';
            foreach ($parents as $parent) {
                try {
                    if ($parent instanceof \Core\Acl\Role\RoleInterface) {
                        $roleParentId = $parent->getRoleId();
                    } else {
                        $roleParentId = $parent;
                    }
                    $roleParent = $this->get($roleParentId);
                } catch (\Core\Acl\Role\Registry\Exception $e) {
                    throw new \Core\Acl\Role\Registry\Exception("Parent Role id '$roleParentId' does not exist", 0, $e);
                }
                $roleParents[$roleParentId] = $roleParent;
                $this->_roles[$roleParentId]['children'][$roleId] = $role;
            }
        }

        $this->_roles[$roleId] = array(
            'instance' => $role,
            'parents'  => $roleParents,
            'children' => array()
            );

        return $this;
    }

    /**
     * Returns the identified Role
     *
     * The $role parameter can either be a Role or a Role identifier.
     *
     * @param  \Core\Acl\Role\RoleInterface|string $role
     * @throws \Core\Acl\Role\Registry\Exception
     * @return \Core\Acl\Role\RoleInterface
     */
    public function get($role)
    {
        if ($role instanceof \Core\Acl\Role\RoleInterface) {
            $roleId = $role->getRoleId();
        } else {
            $roleId = (string) $role;
        }

        if (!$this->has($role)) {
            /**
             * @see \Core\Acl\Role\Registry\Exception
             */
            require_once 'Core/Acl/Role/Registry/Exception.php';
            throw new \Core\Acl\Role\Registry\Exception("Role '$roleId' not found");
        }

        return $this->_roles[$roleId]['instance'];
    }

    /**
     * Returns true if and only if the Role exists in the registry
     *
     * The $role parameter can either be a Role or a Role identifier.
     *
     * @param  \Core\Acl\Role\RoleInterface|string $role
     * @return boolean
     */
    public function has($role)
    {
        if ($role instanceof \Core\Acl\Role\RoleInterface) {
            $roleId = $role->getRoleId();
        } else {
            $roleId = (string) $role;
        }

        return isset($this->_roles[$roleId]);
    }

    /**
     * Returns an array of an existing Role's parents
     *
     * The array keys are the identifiers of the parent Roles, and the values are
     * the parent Role instances. The parent Roles are ordered in this array by
     * ascending priority. The highest priority parent Role, last in the array,
     * corresponds with the parent Role most recently added.
     *
     * If the Role does not have any parents, then an empty array is returned.
     *
     * @param  \Core\Acl\Role\RoleInterface|string $role
     * @uses   \Core\Acl\Role\Registry::get()
     * @return array
     */
    public function getParents($role)
    {
        $roleId = $this->get($role)->getRoleId();

        return $this->_roles[$roleId]['parents'];
    }

    /**
     * Returns true if and only if $role inherits from $inherit
     *
     * Both parameters may be either a Role or a Role identifier. If
     * $onlyParents is true, then $role must inherit directly from
     * $inherit in order to return true. By default, this method looks
     * through the entire inheritance DAG to determine whether $role
     * inherits from $inherit through its ancestor Roles.
     *
     * @param  \Core\Acl\Role\RoleInterface|string $role
     * @param  \Core\Acl\Role\RoleInterface|string $inherit
     * @param  boolean                        $onlyParents
     * @throws \Core\Acl\Role\Registry\Exception
     * @return boolean
     */
    public function inherits($role, $inherit, $onlyParents = false)
    {
        /**
         * @see \Core\Acl\Role\Registry\Exception
         */
        require_once 'Core/Acl/Role/Registry/Exception.php';
        try {
            $roleId     = $this->get($role)->getRoleId();
            $inheritId = $this->get($inherit)->getRoleId();
        } catch (\Core\Acl\Role\Registry\Exception $e) {
            throw new \Core\Acl\Role\Registry\Exception($e->getMessage(), $e->getCode(), $e);
        }

        $inherits = isset($this->_roles[$roleId]['parents'][$inheritId]);

        if ($inherits || $onlyParents) {
            return $inherits;
        }

        foreach ($this->_roles[$roleId]['parents'] as $parentId => $parent) {
            if ($this->inherits($parentId, $inheritId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Removes the Role from the registry
     *
     * The $role parameter can either be a Role or a Role identifier.
     *
     * @param  \Core\Acl\Role\RoleInterface|string $role
     * @throws \Core\Acl\Role\Registry\Exception
     * @return \Core\Acl\Role\Registry Provides a fluent interface
     */
    public function remove($role)
    {
        /**
         * @see \Core\Acl\Role\Registry\Exception
         */
        require_once 'Core/Acl/Role/Registry/Exception.php';
        try {
            $roleId = $this->get($role)->getRoleId();
        } catch (\Core\Acl\Role\Registry\Exception $e) {
            throw new \Core\Acl\Role\Registry\Exception($e->getMessage(), $e->getCode(), $e);
        }

        foreach ($this->_roles[$roleId]['children'] as $childId => $child) {
            unset($this->_roles[$childId]['parents'][$roleId]);
        }
        foreach ($this->_roles[$roleId]['parents'] as $parentId => $parent) {
            unset($this->_roles[$parentId]['children'][$roleId]);
        }

        unset($this->_roles[$roleId]);

        return $this;
    }

    /**
     * Removes all Roles from the registry
     *
     * @return \Core\Acl\Role\Registry Provides a fluent interface
     */
    public function removeAll()
    {
        $this->_roles = array();

        return $this;
    }

    public function getRoles()
    {
        return $this->_roles;
    }

}
