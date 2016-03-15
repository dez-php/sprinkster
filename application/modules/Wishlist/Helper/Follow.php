<?php

namespace Wishlist\Helper;

class Follow {
	
	protected $self;
	
	public $wishlist_id;
	
	public $user_id;
	
	public $is_follow = null;
	
	public $is_follow_user = null;

	public function __construct($wishlist_id, $self = null) {
		
		$wishlistTable = new \Wishlist\Wishlist();
		$wishlistFollowTable = new \Wishlist\WishlistFollow();
		$wishlistFollowIgnoreTable = new \Wishlist\WishlistFollowIgnore();
		$wishlist_info = $wishlistTable->fetchRow(array('id = ?' => $wishlist_id));
		if(!$wishlist_info) {
			return $this;
		}
		
		$this->self = $self instanceof \Core\Db\Table\Row\AbstractRow ? $self : \User\User::getUserData();
		if(!$this->self->id) {
			return $this;
		}

		$this->wishlist_id = $wishlist_id;
		$this->user_id = $wishlist_info->user_id;
		
		$is_follow = $wishlistFollowTable->countByUserId_FollowId($this->self->id, $wishlist_id);
		if($is_follow) {
			$is_follow_disable = $wishlistFollowIgnoreTable->countByUserId_FollowId($this->self->id, $wishlist_id);
			if($is_follow_disable) {
				$is_follow = false;
			}
		}
		if(!$is_follow) {
			$is_follow_user = $this->isFollowUser($this->user_id);
			if($is_follow_user) {
				$is_follow = true;
			}
		}
		
		// if user is follow
		$is_follow_user = new \User\Helper\Follow($this->user_id);
		$this->is_follow_user = $is_follow_user->is_follow;
		
		$this->is_follow = $is_follow ? true : false;
		
	}
	
	public function followWishlist() {

		$wishlistFollowTable = new \Wishlist\WishlistFollow();
		$wishlistFollowIgnoreTable = new \Wishlist\WishlistFollowIgnore();
		$userFollowTable = new \User\UserFollow();
		
		try {
			$wishlistFollowTable->getAdapter()->beginTransaction();
			
			$is_fow = $this->isFollowUser($this->user_id);
			$wishlistFollowTable->delete(array(
				'user_id = ?' => $this->self->id,
				'wishlist_id = ?' => $this->wishlist_id
			));
			if($is_fow) {
				$uf_id = $wishlistFollowIgnoreTable->delete(array(
						'user_id = ?' => $this->self->id,
						'wishlist_id = ?' => $this->wishlist_id
				));
			} else {
				$uf_id = $wishlistFollowTable->insert(array(
						'user_id' => $this->self->id,
						'follow_id' => $this->user_id,
						'wishlist_id' => $this->wishlist_id,
						'date_added' => \Core\Date::getInstance(null,\Core\Date::SQL_FULL,true)->toString()
				));
			}
			
			/*$wishlistFollowTable->delete(array(
				'user_id = ?' => $this->self->id,
				'wishlist_id = ?' => $this->wishlist_id
			));
			
			$uf_id = $wishlistFollowTable->insert(array(
				'user_id' => $this->self->id,
				'follow_id' => $this->user_id,
				'wishlist_id' => $this->wishlist_id,
				'date_added' => \Core\Date::getInstance(null,\Core\Date::SQL_FULL,true)->toString()
			));
			
			if($uf_id) {
				$wishlistFollowIgnoreTable->delete(array(
					'user_id = ?' => $this->self->id,
					'wishlist_id = ?' => $this->wishlist_id
				));
			}*/
			
			// if user is follow
			$is_follow_user = new \User\Helper\Follow($this->user_id);
			$this->is_follow_user = $is_follow_user->is_follow;
			
			$this->updateStat();
			
			$wishlistFollowTable->getAdapter()->commit();
			return $uf_id ? true : false;
		} catch (\Core\Exception $e) {
			$wishlistFollowTable->getAdapter()->rollBack();
		}
		
		return null;
	}
	
	public function unfollowWishlist() {
		
		$wishlistFollowTable = new \Wishlist\WishlistFollow();
		$wishlistFollowIgnoreTable = new \Wishlist\WishlistFollowIgnore();
		$userFollowTable = new \User\UserFollow();
		
		try {
			$wishlistFollowTable->getAdapter()->beginTransaction();
			
			$row = $wishlistFollowTable->delete(array(
					'user_id = ?' => $this->self->id,
					'wishlist_id = ?' => $this->wishlist_id
			));
			
			$is_fow = $this->isFollowUser($this->user_id);
			
			if($row || $is_fow) {
				if($is_fow) {
					$row = $wishlistFollowIgnoreTable->insert(array(
							'user_id' => $this->self->id,
							'follow_id' => $this->user_id,
							'wishlist_id' => $this->wishlist_id,
							'date_added' => \Core\Date::getInstance(null,\Core\Date::SQL_FULL,true)->toString()
					));
				}
			}
			
			$is_follow_user = new \User\Helper\Follow($this->user_id);
			$this->is_follow_user = $is_follow_user->is_follow;
			
			$this->updateStat();
			
			$wishlistFollowTable->getAdapter()->commit();
			return $row ? true : false;
		} catch (\Core\Exception $e) {
			$wishlistFollowTable->getAdapter()->rollBack();
		}
		
		return null;
	}
	
	public static function totalWishlistFollow($user_id) {
		$wishlistFollowTable = new \Wishlist\WishlistFollow();
		return $wishlistFollowTable->countByUserId($user_id);
	}
	
	public function isFollowUser($user_id) {
		if(!$this->self->id || $this->self->id == $user_id) {
			return false;
		}
		$userFollowTable = new \User\UserFollow();
		return $userFollowTable->countByUserId_FollowId($this->self->id,$user_id);
	}
	
	public function updateStat() { 
		$userTable = new \User\User();
		$wishlistTable = new \Wishlist\Wishlist();
		$userFollowTable = new \User\UserFollow();
		$wishlistFollowTable = new \Wishlist\WishlistFollow();
		$wishlistFollowIgnoreTable = new \Wishlist\WishlistFollowIgnore();

		if($this->self->id) {
			$userTable->update(array(
				'following' => new \Core\Db\Expr('( (SELECT COUNT(DISTINCT follow_id) FROM user_follow WHERE user_id = user.id AND follow_id != user.id LIMIT 1) + (SELECT COUNT(DISTINCT follow_id) FROM wishlist_follow WHERE user_id = user.id AND follow_id != user.id LIMIT 1) )'),
				'followers' => new \Core\Db\Expr('( (SELECT COUNT(DISTINCT user_id) FROM user_follow WHERE follow_id = user.id AND user_id != user.id LIMIT 1) + (SELECT COUNT(DISTINCT user_id) FROM wishlist_follow WHERE follow_id = user.id AND user_id != user.id LIMIT 1) )')
			), array('id = ?' => $this->self->id));
		} 
		if($this->user_id) {
			$userTable->update(array(
				'following' => new \Core\Db\Expr('( (SELECT COUNT(DISTINCT follow_id) FROM user_follow WHERE user_id = user.id AND follow_id != user.id LIMIT 1) + (SELECT COUNT(DISTINCT follow_id) FROM wishlist_follow WHERE user_id = user.id AND follow_id != user.id LIMIT 1) )'),
				'followers' => new \Core\Db\Expr('( (SELECT COUNT(DISTINCT user_id) FROM user_follow WHERE follow_id = user.id AND user_id != user.id LIMIT 1) + (SELECT COUNT(DISTINCT user_id) FROM wishlist_follow WHERE follow_id = user.id AND user_id != user.id LIMIT 1) )')
			), array('id = ?' => $this->user_id));
		}
		if($this->wishlist_id) {
			$wishlistTable->update(array(
					'pins' => new \Core\Db\Expr('( SELECT COUNT(DISTINCT pin_id) FROM pin_repin WHERE wishlist_id = wishlist.id LIMIT 1 )'),
					'followers' => new \Core\Db\Expr('( (SELECT COUNT(DISTINCT user_id) FROM user_follow WHERE follow_id = wishlist.user_id AND user_id != wishlist.user_id LIMIT 1) + (SELECT COUNT(DISTINCT user_id) FROM wishlist_follow WHERE wishlist_id = wishlist.id AND follow_id = wishlist.user_id AND user_id != wishlist.user_id LIMIT 1) - (SELECT COUNT(DISTINCT user_id) FROM wishlist_follow_ignore WHERE follow_id = wishlist.user_id AND wishlist_id = wishlist.id AND user_id != wishlist.user_id LIMIT 1) )')
			), array('id = ?' => $this->wishlist_id));
		}
		return true;
	}
	
}