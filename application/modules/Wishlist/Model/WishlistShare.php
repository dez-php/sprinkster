<?php

namespace Wishlist;

class WishlistShare extends \Base\Model\Reference {

	public function __construct($config = array()) {
		parent::__construct($config);
		$this->setRowClass('\Wishlist\WishlistShareRow');
	}
	
	protected $_referenceMap    = array(
			'Wishlist' => array(
					'columns'           => 'wishlist_id',
					'refTableClass'     => 'Wishlist\Wishlist',
					'refColumns'        => 'id'
			),
			'Share' => array(
					'columns'           => 'share_id',
					'refTableClass'     => 'User\User',
					'refColumns'        => 'id'
			),
			'User' => array(
					'columns'           => 'user_id',
					'refTableClass'     => 'User\User',
					'refColumns'        => 'id'
			)
	);

}