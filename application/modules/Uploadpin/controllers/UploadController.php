<?php

namespace Uploadpin;

class UploadController extends \Base\PermissionController {
	
	use \Local\Traits\Allow;
	
	public function init() {
		$this->noLayout(true);
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function indexAction() {
		$return = array();
		$user = \User\User::getUserData();
		if($user->id) {
			if(!file_exists(BASE_PATH . '/cache/tmp/') || !is_dir(BASE_PATH . '/cache/tmp/')) {
				@mkdir(BASE_PATH . '/cache/tmp/', 0777, true);
				@chmod(BASE_PATH . '/cache/tmp/', 0777);
			}

			//delete old files
			$files_delete = glob(BASE_PATH . '/cache/tmp/*.*');
			if($files_delete) {
				for($i=0; $i<min(count($files_delete),250); $i++) {
					if((filemtime($files_delete[$i]) + 3600) < time()) {
						@unlink($files_delete[$i]);
					}
				}
			}
			
			$valid_extensions = $this->getAllowedTypesWithoutPoint();
			$upload = new \Core\File\Upload('file');
			$ext = $upload->getExtension();
			$upload->newFileName = md5(mt_rand(0, 99999999) . time()) . '.' . $ext;
			$result = $upload->handleUpload(BASE_PATH . '/cache/tmp/', $valid_extensions);
			if($result) {
				if( is_array( $image_info = @getimagesize(BASE_PATH . '/cache/tmp/' . $upload->newFileName) ) ) {
					$request = $this->getRequest();
					$data = array();
					$data['media'] = 'cache/tmp/' . $upload->newFileName;
					$pinTable = new \Pin\Pin();
					$this->x_form_cmd = $pinTable->getXFormCmd();

					$data['isXmlHttpRequest'] = $request->isXmlHttpRequest();		
					$data['categories'] = \Category\Category::getCategoryTreeSelect();
				
					$media = $request->getRequest('media');

					$args['status'] = TRUE;

					if(!$media)
						$args['html'] = $this->renderBuffer('index', $data);

					if($media)
						$args['media'] = $request->getBaseUrl() . '/cache/tmp/' . $upload->newFileName;

					$return = $args;
				} else {
					$return = [ 'success' => FALSE, 'errors' => [ $this->_('Unable to get image information' ) ] ];
				}
			} else {
				$return = [ 'success' => FALSE, 'errors' => [ $this->_($upload->getErrorMsg()) ] ];
			}
		} else {
			$return = [ 'success' => FALSE, 'errors' => [ $this->_('You must be logged in to upload files.') ] ];
		}
		$this->responseJsonCallback($return);
	}
	
}