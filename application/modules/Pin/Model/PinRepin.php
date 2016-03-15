<?php

namespace Pin;

class PinRepin extends \Base\Model\Reference {
	
	public function __construct($config = array()) {
		parent::__construct($config);
		$this->setRowClass('\Pin\PinRepinRow');
	}

	protected $_referenceMap = [
		'User' => [
			'columns' => 'user_id',
			'refTableClass' => 'User\User',
			'refColumns' => 'id',
		],

		'Pin' => [
			'columns' => 'pin_id',
			'refTableClass' => 'Pin\Pin',
			'refColumns' => 'id',
		],

		'Wishlist' => [
			'columns' => 'wishlist_id',
			'refTableClass' => 'Wishlist\Wishlist',
			'refColumns' => 'id',
		],
	];
	
	const DefaultWishlistLimit = 8;

	public static function getWishlistRepins($wishlist_id, $order, $limit = self::DefaultWishlistLimit)
	{
		$wishlist_id = (int) $wishlist_id;
		$limit = (int) $limit;

		if(0 >= $wishlist_id)
			return NULL;
		
		$ids = \Core\Db\Init::getDefaultAdapter()->fetchCol('
				SELECT p.id
				FROM pin p
				JOIN pin_repin rp ON (p.id = rp.pin_id)
				WHERE
					rp.wishlist_id = ?
				AND
					p.status = 1
				ORDER BY ?
				LIMIT ?
			',
				
			[ (int) $wishlist_id, new \Core\Db\Expr($order), $limit ]
		);
		
		array_walk($ids, 'intval');
		
		if(empty($ids))
			$ids[] = 0;
		
		return (new \Pin\Pin)->fetchAll([ 'id IN (' . implode(', ', $ids) . ')' ]);
	}
	
}