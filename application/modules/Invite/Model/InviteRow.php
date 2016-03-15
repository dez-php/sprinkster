<?php

namespace Invite;

class InviteRow extends \Core\Db\Table\Row {

    /**
     * Allows pre-insert logic to be applied to row.
     * Subclasses may override this method.
     *
     * @return void
     */
    protected function _insert()
    {
    	$this->date_added = \Core\Date::getInstance(null,\Core\Date::SQL_FULL, true)->toString();
    }

}