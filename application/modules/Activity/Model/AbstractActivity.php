<?php

namespace Activity;

class AbstractActivity {

	/**
	 * @var number
	 */
	protected $user_id;
	/**
	 * @var \Core\Db\Adapter\AbstractAdapter
	 */
	protected $db;
	
	protected $following = '0';
	
	public function __construct($user_id) {
		$this->user_id = $user_id;
		$this->db = \Core\Db\Init::getDefaultAdapter();
		$this->following = \User\UserFollow::userFollowing($this->user_id);
	}
	
	public function method($string, $action = 'action') {
		return new \Core\Db\Expr($this->db->quote($string) . ' AS `' . $action . '`');
	}
	
}