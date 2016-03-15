<?php

namespace Pin;

class PinComment extends \Base\Model\Reference {
	
	public function __construct($config = array()) {
		parent::__construct($config);
		$this->setRowClass('\Pin\PinCommentRow');
	}
	
	protected $_referenceMap    = array(
			'User' => array(
					'columns'           => 'user_id',
					'refTableClass'     => 'User\User',
					'refColumns'        => 'id'
			),
	);
	
	//virtual map for reference
	protected $_referenceReverseMap    = array(
			'User\User' => array(
					'columns'           => 'id',
					'refTableClass'     => 'Pin\PinComment',
					'refColumns'        => 'user_id',
					'singleRow'			=> true
			),
	);
	
	public function getAll($pin_id, $order = 'id DESC', $limit = NULL, $offset = NULL)
	{
		$db = \Core\Db\Init::getDefaultAdapter();
		$sql = $db->select()
					->from($this->_name)
					->joinLeft('user', $this->_name.'.user_id=user.id',array('username','firstname', 'lastname','pins','wishlists','user_likes' => 'likes','followers','avatar_width', 'avatar_height', 'avatar', 'avatar_store_host', 'avatar_store'))
					->where($this->_name.'.pin_id = ?', $pin_id)
					->order($order)
					->limit($limit, $offset);
					
		$rows = $db->fetchAll($sql);

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
	
	public function get($comment_id) {
		$db = \Core\Db\Init::getDefaultAdapter();
		$sql = $db->select()
					->from($this->_name)
					->joinLeft('user', $this->_name.'.user_id=user.id',array('username','firstname', 'lastname','pins','wishlists','user_likes' => 'likes','followers','avatar_width', 'avatar_height', 'avatar', 'avatar_store_host', 'avatar_store'))
					->where($this->_name.'.id = ?', $comment_id)
					->limit(1);
					
		$rows = $db->fetchRow($sql);

		if (!$rows) {
            return null;
        }

        $data = array(
            'table'   => $this,
            'data'     => $rows,
            'readOnly' => true,
            'stored'  => true
        );

        $rowClass = $this->getRowClass();
        if (!class_exists($rowClass)) {
            require_once 'Loader.php';
            \Core\Loader\Loader::loadClass($rowClass);
        }
        return new $rowClass($data);
		
	}
	
}