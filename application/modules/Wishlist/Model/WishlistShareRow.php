<?php

namespace Wishlist;

class WishlistShareRow extends \Core\Db\Table\Row {
	
	
	protected function _delete()
	{ 
		(new \Pin\PinRepin())->delete(['user_id = ?' => $this->share_id, 'wishlist_id = ?' => $this->wishlist_id]);
	}
    
}