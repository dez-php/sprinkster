<?php

namespace User;

class AvatarController extends \Base\PermissionController {
	
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
				$image = \Base\Config::getUploadMethod('Base', 'userAvatars');
				if( is_array($image_info = $image->upload(BASE_PATH . '/cache/tmp/' . $upload->newFileName, $image_path)) ) {
					$old_avatar = $user->avatar;
					$old_store = $user->avatar_store;
					$user->avatar = $image_info['file'];
					$user->avatar_width = $image_info['width'];
					$user->avatar_height = $image_info['height'];
					$user->avatar_store = $image_info['store'];
					$user->avatar_store_host = $image_info['store_host'];
					try {
						$user->save();
						@unlink(BASE_PATH . '/cache/tmp/' . $upload->newFileName);
						$return = array('success' => true, 'file' => \User\Helper\Avatar::getImages($user));
						if($old_avatar && $old_store) {
							\Core\Http\Thread::run(array('\\'.$old_store . '\Helper\Upload','delete','useravatars'),$old_avatar);
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