<?php

namespace Bookmarklet;

class ImageController extends \Base\PermissionController {
	
	private $src_noimage = 'data:image/png;base64,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';
	
	public function init() {
		$this->noLayout(true);
	}
	
	public function indexAction() {			
		$request = $this->getRequest();
		
		$guid = $request->getParam('guid');
		
		if(!file_exists(BASE_PATH . '/cache/' . __NAMESPACE__ . '/') || !is_dir(BASE_PATH . '/cache/' . __NAMESPACE__ . '/')) {
			@mkdir(BASE_PATH . '/cache/' . __NAMESPACE__ . '/', 0777, true);
		}
		
		//delete old files
		$files_delete = glob(BASE_PATH . '/cache/' . __NAMESPACE__ . '/*');
		if($files_delete) {
			for($i=0; $i<min(count($files_delete),250); $i++) {
				if((filemtime($files_delete[$i]) + 3600) < time()) {
					@unlink($files_delete[$i]);
				}
			}
		}
		
		if(file_exists(BASE_PATH . '/cache/' . __NAMESPACE__ . '/' . $guid) && is_file(BASE_PATH . '/cache/' . __NAMESPACE__ . '/' . $guid)) {
			$data = file_get_contents(BASE_PATH . '/cache/' . __NAMESPACE__ . '/' . $guid);
		} else {
			$data = \Core\Session\Base::get(__NAMESPACE__ . $guid);
			if(@file_put_contents(BASE_PATH . '/cache/' . __NAMESPACE__ . '/' . $guid, $data)) {
				\Core\Session\Base::clear(__NAMESPACE__ . $guid);
			}
		}

		if(strpos($data, 'data:image/') !== 0) {
			$data = $this->src_noimage;
		}
		$data = explode(';', substr($data, 5), 2);
		$mime = $data[0];
		$image = isset($data[1]) ? substr($data[1], 7) : '';
		
		$response = $this->getResponse();
		$response->addHeader('Content-type: ' . $mime);
		$response->appendBody(base64_decode($image));
		
	}
	
}