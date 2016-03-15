<?php

namespace Conversation;

class ConversationRow extends \Core\Db\Table\Row {
	
	/**
	 * Allows pre-insert logic to be applied to row.
	 * Subclasses may override this method.
	 *
	 * @return void
	 */
	protected function _insert()
	{
		$this->date_added = \Core\Date::getInstance(null,\Core\Date::SQL_FULL, true)->toString();
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
		$this->date_modified = \Core\Date::getInstance(null,\Core\Date::SQL_FULL, true)->toString();
	}

	public function getUserFullname() {
		$username_method_show = \Base\Config::get('username_show');
		if($username_method_show == 'fullname') {
			return $this->firstname . ' ' . $this->lastname;
		} else if($username_method_show == 'fullname-desc') {
			return $this->lastname . ' ' . $this->firstname;
		} else if($username_method_show == 'firstname') {
			return $this->firstname;
		} else {
			return $this->username;
		}
	}
	
}