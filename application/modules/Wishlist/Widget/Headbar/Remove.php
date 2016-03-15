<?php

namespace Wishlist\Widget\Headbar;

class Remove extends \Wishlist\Widget\Headbar {

	public function result() {
		$return = array();
		$user = \User\User::getUserData();
		$wishlistTable = new \Wishlist\Wishlist();
		$wishlist = $wishlistTable->fetchRow($wishlistTable->makeWhere(array('id' => $this->getRequest()->getQuery('bid'))));
		if($user->id && $wishlist && $wishlist->user_id == $user->id) {
			if(!file_exists(BASE_PATH . '/cache/tmp/') || !is_dir(BASE_PATH . '/cache/tmp/')) {
				@mkdir(BASE_PATH . '/cache/tmp/', 0777, true);
				@chmod(BASE_PATH . '/cache/tmp/', 0777);
			}

			if($wishlist->cover && $wishlist->cover_store) {
				$storeName = $this->getFrontController()->formatHelperName('\\'.$wishlist->cover_store . '\Helper\Upload');
				$store = new $storeName('Base', 'wishlistCovers');
				$store->delete($wishlist->cover);
			}
			try {
				$wishlist->cover = null;
				$wishlist->cover_width = 0;
				$wishlist->cover_height = 0;
				$wishlist->cover_store = null;
				$wishlist->cover_store_host = null;
				$wishlist->save();
				$return = array('file' => \Wishlist\Helper\Cover::getImages($wishlist));
			} catch (\Core\Exception $e) {
				$return = array('error' => $e->getMessage());
			}

		} else {
			$return = array('success' => false, 'msg' => $this->_('You must be logged in to remove files.'));
		}
		$this->responseJsonCallback($return);
	}
	
}