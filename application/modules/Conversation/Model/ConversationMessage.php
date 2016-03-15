<?php

namespace Conversation;

class ConversationMessage extends \Base\Model\Reference {
	
	protected $_order = 'id DESC';
	
	public function __construct($config = array()) {
		parent::__construct($config);
		$this->setRowClass('\Conversation\ConversationMessageRow');
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
	);
	
}