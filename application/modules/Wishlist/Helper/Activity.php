<?php

namespace Wishlist\Helper;

class Activity extends \Activity\AbstractActivity {
	
	public function sqlCreate() {
		$sql = $this->db->select()
						->from('wishlist', array($this->method(0,'pin_id'),'id AS wishlist_id','date_added', 'user_id',$this->method('createWishlist')))
						->joinLeft('user', 'wishlist.user_id=user.id',array('username','firstname', 'lastname','pins','wishlists','user_likes' => 'likes','followers','avatar_width', 'avatar_height', 'avatar', 'avatar_store_host', 'avatar_store'))
						->where('wishlist.user_id IN (?)', $this->following)
						->where('user.status = 1')
						->order('wishlist.id DESC')
						->limit(100);
		return $sql;
	}
	
}