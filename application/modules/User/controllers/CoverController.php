<?php

namespace User;

class CoverController extends \Base\PermissionController {
	
	use \Local\Traits\Allow;
	
	public function init() {
		$this->noLayout(true);
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function indexAction() {
		$return = array();
		$user = \User\User::getUserData();
		if($user->id && $this->getRequest()->getRequest('user_id') == $user->id) {
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
				$image_path = '/users' . \Core\Date::getInstance($user->date_added, '/yy/mm/', true);
				$image = \Base\Config::getUploadMethod('Base', 'userCovers');
				if( is_array($image_info = $image->upload(BASE_PATH . '/cache/tmp/' . $upload->newFileName, $image_path)) ) {
					$old_cover = $user->cover;
					$old_store = $user->cover_store;
					$user->cover = $image_info['file'];
					$user->cover_top = 0;
					$user->cover_width = $image_info['width'];
					$user->cover_height = $image_info['height'];
					$user->cover_store = $image_info['store'];
					$user->cover_store_host = $image_info['store_host'];
					try {
						$user->save();
						@unlink(BASE_PATH . '/cache/tmp/' . $upload->newFileName);
						$return = array('success' => true, 'file' => \User\Helper\Cover::getImages($user));
						if($old_cover && $old_store) {
							$storeName = $this->getFrontController()->formatHelperName('\\'.$old_store . '\Helper\Upload');
							$store = new $storeName('Base', 'userCovers');
							\Core\Http\Thread::run(array($store,'delete'),$old_cover);
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
	
	public function removeAction() {
		$return = array();
		$user = \User\User::getUserData();
		if($user->id && $this->getRequest()->getRequest('user_id') == $user->id) {
			$old_cover = $user->cover;
			$old_store = $user->cover_store;
			$user->cover = null;
			$user->cover_top = 0;	
			$user->cover_width = 0;
			$user->cover_height = 0;
			$user->cover_store = null;
			$user->cover_store_host = null;
			try {
				$user->save();
				$return = array('file' => \User\Helper\Cover::getImages($user));
				if($old_cover && $old_store) {
					$storeName = $this->getFrontController()->formatHelperName('\\'.$old_store . '\Helper\Upload');
					$store = new $storeName('Base', 'userCovers');
					\Core\Http\Thread::run(array($store,'delete'),$old_cover);
				}
			} catch (\Core\Exception $e) {
				$return = array('error' => $e->getMessage());
			}
		} else {
			$return = array('error' => $this->_('You must be logged in to upload files.'));
		}
		$this->responseJsonCallback($return);
	}
	
	public function repositionAction() {
		$return = array();
		$user = \User\User::getUserData();
		if($user->id && $this->getRequest()->getRequest('user_id') == $user->id) {
			$old_cover = $user->cover;
			$old_store = $user->cover_store;
			$user->cover_top = (float)$this->getRequest()->getParam('top');
			try {
				$user->save();
				$return = array('ok' => true);
			} catch (\Core\Exception $e) {
				$return = array('error' => $e->getMessage());
			}
		} else {
			$return = array('error' => $this->_('You must be logged in to upload files.'));
		}
		$this->responseJsonCallback($return);
	}
	
}