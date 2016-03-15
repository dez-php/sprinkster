<?php

namespace Wishlist\Widget;

class Share  extends \Base\Widget\PermissionWidget {

	protected $use_index;
	protected $force_index;
	protected $options = array();
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function setUseIndex($index) {
		$this->use_index = $index;
		return $this;
	}
	
	public function setForceIndex($index) {
		$this->force_index = $index;
		return $this;
	}
	
	/**
	 * @return \Core\Db\Table\Rowset\AbstractRowset
	 */
	protected function getWishlists($user_id) {
		$wishlistTable = new \Wishlist\Wishlist();
		if($user_id) {
			$sharedTable = new \Wishlist\WishlistShare();
			$shared = $sharedTable->select()
									->from($sharedTable,'wishlist_id')
									->where('accept IS NULL')
									->where($sharedTable->makeWhere(array('share_id'=>$user_id)));
	
			return $wishlistTable->getAll( $wishlistTable->makeWhere(array('id' => array($shared))), null, 100000, 0 );	
		} else {
			return $wishlistTable->fetchAll( array('id=?'=>0), null, 0 );
		}
	}
	
	public function result() {
		$user_request_id = $this->getRequest()->getParam('user_id');
		$data = array();
		/**
		 * get wishlists
		 */
		$self = \User\User::getUserData();
		if($user_request_id && $self->id && $user_request_id == $self->id) {
			$data['wishlists'] = $this->getWishlists($self->id);
	
			$this->render('grid', $data);
		}
	}

}