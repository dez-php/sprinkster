<?php

namespace Pin\Helper;

class Activity extends \Activity\AbstractActivity {
	
	public function sqlCreate() {
		$sql = $this->db->select()
						->from('pin', array('id AS pin_id','wishlist_id','date_added', 'user_id',$this->method('createPin')))
						->joinLeft('user', 'pin.user_id=user.id',array('username','firstname', 'lastname','pins','wishlists','user_likes' => 'likes','followers','avatar_width', 'avatar_height', 'avatar', 'avatar_store_host', 'avatar_store'))
						->where('pin.user_id IN (?)', $this->following)
						->where('user.status = 1')
						->order('pin.id DESC')
						->limit(100);
		return $sql;
	}
	
	public function sqlLike() {
		$sql = $this->db->select()
						->from('pin_like', array('pin_id','wishlist_id','date_added', 'user_id',$this->method('likePin')))
						->joinLeft('user', 'pin_like.user_id=user.id',array('username','firstname', 'lastname','pins','wishlists','user_likes' => 'likes','followers','avatar_width', 'avatar_height', 'avatar', 'avatar_store_host', 'avatar_store'))
						->where('pin_like.user_id IN (?)', $this->following)
						->where('user.status = 1')
						->order('pin_like.id DESC')
						->limit(100);
		return $sql;
	}
	
}