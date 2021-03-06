<?php

namespace Paymentgateway;

class OrderStatusRow extends \Core\Db\Table\Row {

    /**
     * Allows pre-insert logic to be applied to row.
     * Subclasses may override this method.
     *
     * @return void
     */
    protected function _insert()
    {
    	$this->date_added = \Core\Date::getInstance(NULL, \Core\Date::SQL_FULL, TRUE)->toString();
    	$this->date_modified = $this->date_added;
    }

    /**
     * Allows pre-update logic to be applied to row.
     * Subclasses may override this method.
     *
     * @return void
     */
    protected function _update()
    {
    	$this->date_modified = \Core\Date::getInstance(NULL, \Core\Date::SQL_FULL, TRUE)->toString();
    }
	
}