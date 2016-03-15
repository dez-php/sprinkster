<?php

namespace Activity;

class Activity extends \Base\Model\Reference {

	const REPIN = 'REPIN';
	const UNREPIN = 'UNREPIN';
	const FOLLOW = 'FOLLOW';
	const ADDPIN = 'ADDPIN';
	const LIKEPIN = 'LIKEPIN';
	const COMMENTPIN = 'COMMENTPIN';
	const MENTIONED = 'MENTIONED';
	const INVITEWISHLIST = 'INVITEWISHLIST';
	const INVITEWISHLISTALLOW = 'INVITEWISHLISTALLOW';
	const SHIPPING = 'SHIPPING';

	public function __construct($config = array()) {
		parent::__construct($config);
		$this->setRowClass('\Activity\ActivityRow');
	}
	
	public function fetchUnion($sql, $limit = 10) {
		$sql = $this->getAdapter()->select()->union($sql)
					->order('date_added DESC')
					->limit($limit);
		$rows = $this->getAdapter()->fetchAll($sql);

        $data  = array(
            'table'    => $this,
            'data'     => $rows,
            'readOnly' => true,
            'rowClass' => $this->getRowClass(),
            'stored'   => true
        );

        $rowsetClass = $this->getRowsetClass();
        if (!class_exists($rowsetClass)) {
            require_once 'Loader.php';
            \Core\Loader\Loader::loadClass($rowsetClass);
        }

        return new $rowsetClass($data);
		
	}
	
	public function getAll($where = null, $order = null, $count = null, $offset = null) {
		
		$sql = $this->getAdapter()->select()
					->from($this->_name)
					->joinLeft('user', 'from_user_id = user.id',array('username','firstname', 'lastname','pins','wishlists','user_likes' => 'likes','followers','avatar_width', 'avatar_height', 'avatar', 'avatar_store_host', 'avatar_store'))
					->joinLeft('wishlist', $this->_name . '.wishlist_id=wishlist.id', 'title AS wishlist')
					->joinLeft('pin', $this->_name . '.pin_id=pin.id', 'title AS pin')
					->order($order)
					->limit($count, $offset);
		if($where) {
			$sql->where($where);
		}
		
		$rows = $this->getAdapter()->fetchAll($sql);

        $data  = array(
            'table'    => $this,
            'data'     => $rows,
            'readOnly' => true,
            'rowClass' => $this->getRowClass(),
            'stored'   => true
        );

        $rowsetClass = $this->getRowsetClass();
        if (!class_exists($rowsetClass)) {
            require_once 'Loader.php';
            \Core\Loader\Loader::loadClass($rowsetClass);
        }

        return new $rowsetClass($data);
		
	}
	
	////////////////////////
	public static function set($to, $type, $pin_id = null, $wishlist_id = null, $comment = null) {
		
		(new self())->delete(['date_added < DATE_ADD(NOW(), INTERVAL -2 MONTH)']);
		
		$self = \User\User::getUserData();
		if(!$self->id || $self->id == $to) {
			return;
		}
		
		$selfTable = new self();
		$history_id = $selfTable->insert(array(
			'date_added' => \Core\Date::getInstance(null,\Core\Date::SQL_FULL, true)->toString(),
			'from_user_id' => $self->id,
			'to_user_id' => $to,
			'action' => $type,
			'pin_id' => $pin_id,
			'wishlist_id' => $wishlist_id,
			'comment' => $comment
		));
	}
	
	public static function setFromTo($from, $to, $type, $pin_id = null, $wishlist_id = null, $comment = null)
	{
		if (!$from || !$to) {
			return;
		}

		$selfTable = new self();
		$history_id = $selfTable->insert(array(
			'date_added' => \Core\Date::getInstance(null, \Core\Date::SQL_FULL, true)->toString(),
			'from_user_id' => $from,
			'to_user_id' => $to,
			'action' => $type,
			'pin_id' => $pin_id,
			'wishlist_id' => $wishlist_id,
			'comment' => $comment
		));
	}

	public static function remove($to, $type, $pin_id = null, $wishlist_id = null) {
		
		(new self())->delete(['date_added < DATE_ADD(NOW(), INTERVAL -2 MONTH)']);
		
		$self = \User\User::getUserData();
		if(!$self->id || $self->id == $to) {
			return;
		}

		$selfTable = new self();
		$history_id = $selfTable->delete($selfTable->makeWhere(array(
			'from_user_id' => $self->id,
			'to_user_id' => $to,
			'action' => $type,
			'pin_id' => $pin_id,
			'wishlist_id' => $wishlist_id
		)));
	}
	
}