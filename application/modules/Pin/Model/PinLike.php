<?php

namespace Pin;

class PinLike extends \Base\Model\Reference {
	
	public function __construct($config = array()) {
		parent::__construct($config);
		$this->setRowClass('\Pin\PinLikeRow');
	}
	
	protected $_referenceMap    = array(
			'Pin' => array(
					'columns'           => 'pin_id',
					'refTableClass'     => 'Pin\Pin',
					'refColumns'        => 'id'
			)
	);
	
	public static function userLikes($user_id) {
		$self = new self();

		return $self->select()->from($self,'pin_id')->where('user_id = ?', $user_id);
	}
	
}