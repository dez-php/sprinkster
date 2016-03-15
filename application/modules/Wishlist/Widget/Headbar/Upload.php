<?php

namespace Wishlist\Widget\Headbar;

class Upload extends \Wishlist\Widget\Headbar {

    use \Local\Traits\Allow;

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
			$valid_extensions = $this->getAllowedTypesWithoutPoint();
			$upload = new \Core\File\Upload('file');
			$ext = $upload->getExtension();
			$upload->newFileName = md5(mt_rand(0, 99999999) . time()) . '.' . $ext;
			$result = $upload->handleUpload(BASE_PATH . '/cache/tmp/', $valid_extensions);
			if($result) {
				$image_path = '/wishlist/covers' . \Core\Date::getInstance($wishlist->date_added, '/yy/mm/', true);
				$image = \Base\Config::getUploadMethod('Base', 'wishlistCovers');
				if( is_array($image_info = $image->upload(BASE_PATH . '/cache/tmp/' . $upload->newFileName, $image_path)) ) {
					$old_cover = $wishlist->cover;
					$old_store = $wishlist->cover_store;
					$wishlist->cover = $image_info['file'];
					$wishlist->cover_width = $image_info['width'];
					$wishlist->cover_height = $image_info['height'];
					$wishlist->cover_store = $image_info['store'];
					$wishlist->cover_store_host = $image_info['store_host'];
					try {
						$wishlist->save();
						@unlink(BASE_PATH . '/cache/tmp/' . $upload->newFileName);
						$return = array('success' => true, 'file' => \Wishlist\Helper\Cover::getImages($wishlist));
						if($old_cover && $old_store) {
							$storeName = $this->getFrontController()->formatHelperName('\\'.$old_store . '\Helper\Upload');
							$store = new $storeName('wishlistcovers');
							$store->delete($old_cover);
						}
					} catch (\Core\Exception $e) {
						$return = array('success' => false, 'msg' => $e->getMessage());
					}
				} else {
					$return = array('success' => false, 'msg' => $image->getError());
				}
			} else {
				$return = array('success' => false, 'msg' => $this->_($upload->getErrorMsg()));
			}
		} else {
			$return = array('success' => false, 'msg' => $this->_('You must be logged in to upload files.'));
		}
		$this->responseJsonCallback($return);
	}
	
}