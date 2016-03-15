<?php

namespace Core\Acl\Assert;

interface AssertInterface
{
    /**
     * Returns true if and only if the assertion conditions are met
     *
     * This method is passed the ACL, Role, Resource, and privilege to which the authorization query applies. If the
     * $role, $resource, or $privilege parameters are null, it means that the query applies to all Roles, Resources, or
     * privileges, respectively.
     *
     * @param  \Core\Acl                    $acl
     * @param  \Core\Acl\Role\RoleInterface     $role
     * @param  \Core\Acl\Resource\ResourceInterface $resource
     * @param  string                      $privilege
     * @return boolean
     */
    public function assert(\Core\Acl $acl, \Core\Acl\Role\RoleInterface $role = null, \Core\Acl\Resource\ResourceInterface $resource = null,
                           $privilege = null);
}
