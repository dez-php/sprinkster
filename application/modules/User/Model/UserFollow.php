<?php

namespace User;

class UserFollow extends \Base\Model\Reference {
	
	public static function updateStat($user_id, $wishlist_id = null) {
		$self = \User\User::getUserData();
		if($self->id) {
			$db = \Core\Db\Init::getDefaultAdapter();
			$db->update('user', array(
			'following' => new \Core\Db\Expr('( (SELECT COUNT(DISTINCT follow_id) FROM user_follow WHERE user_id = user.id AND follow_id != user.id LIMIT 1) + (SELECT COUNT(DISTINCT follow_id) FROM wishlist_follow WHERE user_id = user.id AND follow_id != user.id LIMIT 1) )'),
			'followers' => new \Core\Db\Expr('( (SELECT COUNT(DISTINCT user_id) FROM user_follow WHERE follow_id = user.id AND user_id != user.id LIMIT 1) + (SELECT COUNT(DISTINCT user_id) FROM wishlist_follow WHERE follow_id = user.id AND user_id != user.id LIMIT 1) )')
			), array('id = ?' => $self->id));
			$db->update('user', array(
			'following' => new \Core\Db\Expr('( (SELECT COUNT(DISTINCT follow_id) FROM user_follow WHERE user_id = user.id AND follow_id != user.id LIMIT 1) + (SELECT COUNT(DISTINCT follow_id) FROM wishlist_follow WHERE user_id = user.id AND follow_id != user.id LIMIT 1) )'),
			'followers' => new \Core\Db\Expr('( (SELECT COUNT(DISTINCT user_id) FROM user_follow WHERE follow_id = user.id AND user_id != user.id LIMIT 1) + (SELECT COUNT(DISTINCT user_id) FROM wishlist_follow WHERE follow_id = user.id AND user_id != user.id LIMIT 1) )')
			), array('id = ?' => $user_id));
		
			if($wishlist_id) {
				$db->update('wishlist', array(
				'followers' => new \Core\Db\Expr('( (SELECT COUNT(DISTINCT user_id) FROM user_follow WHERE follow_id = wishlist.user_id AND user_id != wishlist.user_id LIMIT 1) + (SELECT COUNT(DISTINCT user_id) FROM wishlist_follow WHERE follow_id = wishlist.user_id AND user_id != wishlist.user_id LIMIT 1) - (SELECT COUNT(DISTINCT user_id) FROM wishlist_follow_ignore WHERE follow_id = wishlist.user_id AND wishlist_id = wishlist.id AND user_id != wishlist.user_id LIMIT 1) )')
				), array('id = ?' => $wishlist_id));
			} else {
				$db->update('wishlist', array(
						'followers' => new \Core\Db\Expr('( (SELECT COUNT(DISTINCT user_id) FROM user_follow WHERE follow_id = wishlist.user_id AND user_id != wishlist.user_id LIMIT 1) + (SELECT COUNT(DISTINCT user_id) FROM wishlist_follow WHERE follow_id = wishlist.user_id AND user_id != wishlist.user_id LIMIT 1) - (SELECT COUNT(DISTINCT user_id) FROM wishlist_follow_ignore WHERE follow_id = wishlist.user_id AND wishlist_id = wishlist.id AND user_id != wishlist.user_id LIMIT 1) )')
				), array('user_id = ?' => $user_id));
			}
		}
	}
	
	public static function userFollowingCategory($user_id) {
		$sql1Table = new \Category\CategoryFollow();
		return $sql1Table->select()->from($sql1Table,'category_id')->where('user_id = ?', $user_id);
	}
	
	public static function userFollowingWithoutWishlists($user_id) {
		$self = new self();
		return $self->select()->from($self,'follow_id')->where('user_id = ?', $user_id);
	}
	
	public static function userFollowingWishlists($user_id) {
		$sql1Table = new \Wishlist\WishlistFollow();
		return $sql1Table->select()->from($sql1Table,'wishlist_id')->where('user_id = ?', $user_id);
	}
	
	public static function userFollowing($user_id) {
		$self = new self();
		$sql1Table = new \Wishlist\WishlistFollow();
		$sql = array($sql1Table->select()->from($sql1Table,'follow_id')->where('user_id = ?', $user_id));
		$sql[] = $self->select()->from($self,'follow_id')->where('user_id = ?', $user_id);
		return $self->getAdapter()->select()->union($sql);
	}
	
	public static function userFollowers($user_id) {
		$self = new self();
		$sql1Table = new \Wishlist\WishlistFollow();
		$sql = array($sql1Table->select()->from($sql1Table,'user_id')->where('follow_id = ?', $user_id));
		$sql[] = $self->select()->from($self,'user_id')->where('follow_id = ?', $user_id);
		return $self->getAdapter()->select()->union($sql);
	}
	
	public static function userFollowingArray($user_id) {
        $db = \Core\Db\Init::getDefaultAdapter();
        $sql = [];
        $self = new self();
        $sql1Table = new \Wishlist\WishlistFollow();
        // wishlist follow
        $sql[] = $sql1Table->select()->from($sql1Table,array('follow_id','follow_id'))->where('user_id = ?', $user_id);
        // user follow
        $sql[] = $self->select()->from($self,array('follow_id','follow_id'))->where('user_id = ?', $user_id);
        // category follow
        $sql[] = $db->select()->from('category_follow', '')->joinLeft('pin', 'pin.category_id = category_follow.category_id', array('pin.user_id', 'pin.user_id'))->where('category_follow.user_id = ?', $user_id);

        // store
        if(\Core\BaSe\Action::getInstance()->isModuleAccessible('Store')) {
            $sql[] = $db->select()->from('store_like', '')->joinLeft('store_settings', 'store_settings.id = store_like.store_id', array('user', 'user'))->where('store_like.user_id = ?', $user_id);
        }

        return $self->getAdapter()->fetchPairs($self->getAdapter()->select()->union($sql)->limit(5000));
	}
	
	public static function userFollowingArrayWithSelf($user_id) {
        $db = \Core\Db\Init::getDefaultAdapter();
        $sql = [];
		$self = new self();
		$sql1Table = new \Wishlist\WishlistFollow();
		// wishlist follow
        $sql[] = $sql1Table->select()->from($sql1Table,array('follow_id','follow_id'))->where('user_id = ?', $user_id);
		// user follow
        $sql[] = $self->select()->from($self,array('follow_id','follow_id'))->where('user_id = ?', $user_id);
        // category follow
        $sql[] = $db->select()->from('category_follow', '')->joinLeft('pin', 'pin.category_id = category_follow.category_id', array('pin.user_id', 'pin.user_id'))->where('category_follow.user_id = ?', $user_id);

        // store
        if(\Core\BaSe\Action::getInstance()->isModuleAccessible('Store')) {
            $sql[] = $db->select()->from('store_like', '')->joinLeft('store_settings', 'store_settings.id = store_like.store_id', array('user', 'user'))->where('store_like.user_id = ?', $user_id);
        }

		$result = $self->getAdapter()->fetchPairs($self->getAdapter()->select()->union($sql)->limit(5000));
		return array_merge($result, array($user_id=>$user_id));
	}
	
}