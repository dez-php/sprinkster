<?php

namespace Conversation;

class Conversation extends \Base\Model\Reference {
	
	public function __construct($config = array()) {
		parent::__construct($config);
		$this->setRowClass('\Conversation\ConversationRow');
	}
	
	protected $_referenceMap    = array(
			'User' => array(
					'columns'           => 'user_id',
					'refTableClass'     => 'User\User',
					'refColumns'        => 'id'
			),
			'ToUser' => array(
					'columns'           => 'to_user_id',
					'refTableClass'     => 'User\User',
					'refColumns'        => 'id'
			),
			'UnRead' => array(
					'columns'           => 'id',
					'refTableClass'     => 'Conversation\ConversationMessage',
					'refColumns'        => 'conversation_id',
					'where'				=> '`read` = 0'
			),
			'Message' => array(
					'columns'           => 'id',
					'refTableClass'     => 'Conversation\ConversationMessage',
					'refColumns'        => 'conversation_id'
			),
	);

	public function getAll($where = null, $order = null, $count = null, $offset = null) {
		$me = \User\User::getUserData();

		$sql = $this->getAdapter()->select()
					->from($this->_name)
					->joinLeft('user', '(user_id = user.id AND user_id <> ' . $me->id . ' OR to_user_id = user.id AND to_user_id <> ' . $me->id . ')', [ 'username','firstname', 'lastname','pins','wishlists','user_likes' => 'likes','followers','avatar_width', 'avatar_height', 'avatar', 'avatar_store_host', 'avatar_store' ])
					->order($order)
					->limit($count, $offset);
		if($where) {
			$sql->where($where);
		}
		
		$rows = $this->getAdapter()->fetchAll($sql);

		$data = [
			'table'    => $this,
			'data'     => $rows,
			'readOnly' => true,
			'rowClass' => $this->getRowClass(),
			'stored'   => true
		];

		$rowsetClass = $this->getRowsetClass();
		if (!class_exists($rowsetClass))
		{
			require_once 'Loader.php';
			\Core\Loader\Loader::loadClass($rowsetClass);
		}

		return new $rowsetClass($data);
		
	}
	
}