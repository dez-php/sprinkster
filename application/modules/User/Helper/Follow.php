<?php

namespace User\Helper;

class Follow {
	
	public $user_id;
	public $user_id2;

	public $is_follow = null;
	public $system = 0;
	private $self;

	public function __construct($user_id, $user_id2 = null) {
		
		if($user_id2 === null) {
			$self = \User\User::getUserData();
			if(!$self->id || $self->id == $user_id) {
				return $this;
			}
			$user_id2 = $self->id;
		} else {
			$userTable = new \User\User();
			$self = $userTable->fetchRow($userTable->makeWhere(array('id'=>$user_id2)));
		}
		
		$this->self = $self;
		
		$this->user_id2 = $user_id2;
		
		$userTable = new \User\User();
		$user_info = $userTable->fetchRow(array('id = ?' => $user_id));
		
		if(!$user_info) {
			return $this;
		}
		
		$this->user_id = $user_id;
		
		$userFollowTable = $this->self->status==1 ? new \User\UserFollow() : new \User\UserFollowTemp();
		$is_follow = $userFollowTable->countByUserId_FollowId($this->user_id2,$user_id);
		
		if(!$is_follow) {
			$wishlistFollowTable = new \Wishlist\WishlistFollow();
			$is_follow = $wishlistFollowTable->countByUserId_FollowId($this->user_id2, $user_id);
		}
		
		$this->is_follow = $is_follow ? true : false;
		
	}
	
	public function followUser() {
		
		$wishlistFollowTable = new \Wishlist\WishlistFollow();
		$wishlistFollowIgnoreTable = new \Wishlist\WishlistFollowIgnore();
		$userFollowTable = $this->self->status==1 ? new \User\UserFollow() : new \User\UserFollowTemp();
		
		$wishlistFollowTable->getAdapter()->beginTransaction();
		try {
			$uf_id = $userFollowTable->insert(array(
					'user_id' => $this->user_id2,
					'follow_id' => $this->user_id,
					'system' => $this->system,
					'date_added' => \Core\Date::getInstance(null,\Core\Date::SQL_FULL,true)->toString()
			));
			
			if($uf_id) {
				$wishlistFollowIgnoreTable->delete(array(
						'user_id = ?' => $this->user_id2,
						'follow_id = ?' => $this->user_id
				));
			}
			
			$this->updateStat();
			
			$wishlistFollowTable->getAdapter()->commit();
			return $uf_id ? true : false;
		} catch (\Core\Exception $e) {
			$wishlistFollowTable->getAdapter()->rollBack();
		}
		
		return null;
	}
	
	public function unfollowUser() {
		
		$wishlistFollowTable = new \Wishlist\WishlistFollow();
		$wishlistFollowIgnoreTable = new \Wishlist\WishlistFollowIgnore();
		$userFollowTable = $this->self->status==1 ? new \User\UserFollow() : new \User\UserFollowTemp();
		
		try {
			$wishlistFollowTable->getAdapter()->beginTransaction();
			
			$row = $userFollowTable->delete(array(
					'user_id = ?' => $this->user_id2,
					'follow_id = ?' => $this->user_id
			));
			
			if(!$row) {
				$row = $wishlistFollowTable->delete(array(
						'user_id = ?' => $this->user_id2,
						'follow_id = ?' => $this->user_id
				));
			}
			
			if($row) {
				$wishlistFollowIgnoreTable->delete(array(
						'user_id = ?' => $this->user_id2,
						'follow_id = ?' => $this->user_id
				));
				$wishlistFollowTable->delete(array(
						'user_id = ?' => $this->user_id2,
						'follow_id = ?' => $this->user_id
				));				
			}
			
			$this->updateStat();
			
			$wishlistFollowTable->getAdapter()->commit();
			return $row ? true : false;
		} catch (\Core\Exception $e) {
			$wishlistFollowTable->getAdapter()->rollBack();
		}
		
		return null;
	}
	
	public function updateStat() {
		$userTable = new \User\User();
		$wishlistTable = new \Wishlist\Wishlist();

		/*if($this->user_id2) {
			$userTable->update(array(
				'following' => new \Core\Db\Expr('( (SELECT COUNT(DISTINCT follow_id) FROM user_follow WHERE user_id = user.id AND follow_id != user.id LIMIT 1) + (SELECT COUNT(DISTINCT follow_id) FROM wishlist_follow WHERE user_id = user.id AND follow_id != user.id LIMIT 1) )'),
				'followers' => new \Core\Db\Expr('( (SELECT COUNT(DISTINCT user_id) FROM user_follow WHERE follow_id = user.id AND user_id != user.id LIMIT 1) + (SELECT COUNT(DISTINCT user_id) FROM wishlist_follow WHERE follow_id = user.id AND user_id != user.id LIMIT 1) )')
			), array('id = ?' => $this->user_id2));
		} 
		if($this->user_id) {
			$userTable->update(array(
				'following' => new \Core\Db\Expr('( (SELECT COUNT(DISTINCT follow_id) FROM user_follow WHERE user_id = user.id AND follow_id != user.id LIMIT 1) + (SELECT COUNT(DISTINCT follow_id) FROM wishlist_follow WHERE user_id = user.id AND follow_id != user.id LIMIT 1) )'),
				'followers' => new \Core\Db\Expr('( (SELECT COUNT(DISTINCT user_id) FROM user_follow WHERE follow_id = user.id AND user_id != user.id LIMIT 1) + (SELECT COUNT(DISTINCT user_id) FROM wishlist_follow WHERE follow_id = user.id AND user_id != user.id LIMIT 1) )')
			), array('id = ?' => $this->user_id));
			
			$wishlistTable->update(array(
					'followers' => new \Core\Db\Expr('( (SELECT COUNT(DISTINCT user_id) FROM user_follow WHERE follow_id = wishlist.user_id AND user_id != wishlist.user_id LIMIT 1) + (SELECT COUNT(DISTINCT user_id) FROM wishlist_follow WHERE follow_id = wishlist.user_id AND user_id != wishlist.user_id LIMIT 1) - (SELECT COUNT(DISTINCT user_id) FROM wishlist_follow_ignore WHERE follow_id = wishlist.user_id AND wishlist_id = wishlist.id AND user_id != wishlist.user_id LIMIT 1) )')
			), array('user_id = ?' => $this->user_id));
		}*/
		$userTable->updateInfo($this->user_id2);
		$userTable->updateInfo($this->user_id);
		$wishlistTable->update(array(
				'followers' => new \Core\Db\Expr('( (SELECT COUNT(DISTINCT user_id) FROM user_follow WHERE follow_id = wishlist.user_id AND user_id != wishlist.user_id LIMIT 1) + (SELECT COUNT(DISTINCT user_id) FROM wishlist_follow WHERE follow_id = wishlist.user_id AND user_id != wishlist.user_id LIMIT 1) - (SELECT COUNT(DISTINCT user_id) FROM wishlist_follow_ignore WHERE follow_id = wishlist.user_id AND wishlist_id = wishlist.id AND user_id != wishlist.user_id LIMIT 1) )')
		), array('user_id = ?' => $this->user_id));
		
	}
	
}