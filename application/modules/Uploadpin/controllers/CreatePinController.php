<?php

namespace Uploadpin;

class CreatePinController extends \Base\PermissionController {
	
	use \Local\Traits\Allow;

	public function init() {
		$request = $this->getRequest();
		if($request->isXmlHttpRequest()) {
			$this->noLayout(true);
		}
		set_time_limit(0);
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() {
		$request = $this->getRequest();
		
		$data = array(
			'location' => false
		);
		$userInfo = \User\User::getUserData();
		
		if($userInfo->id) {
		
			$pinTable = new \Pin\Pin();

			$this->x_form_cmd = $pinTable->getXFormCmd();
			
			$data['pin_media'] = $request->getRequest('media');
		
			if( $this->validate() ) {
				//create pin
				$sourceTable = new \Source\Source();
				$new = $pinTable->fetchNew();
				$new->category_id = $request->getRequest('category_id');
				$new->user_id = $userInfo->id;
				$new->pinned_from = 'Uploaded';
				$new->title = $this->escape($request->getRequest('title'));
				$new->description = $this->filterWysiwyg($request->getRequest('description'));
				$new->module = $this->getRequest()->getModule();

				
				$pinTable->getAdapter()->beginTransaction();
				$image = \Base\Config::getUploadMethod('Base', 'pinThumbs');
				try {
					$pin_id = $new->save();
					if($pin_id) {
						//upload image
						$image_path = '/pins' . \Core\Date::getInstance($new->date_added, '/yy/mm/', true);
						if( is_array($image_info = $image->upload($data['pin_media'], $image_path)) ) {
							$new->image = $image_info['file'];
							$new->width = $image_info['width'];
							$new->height = $image_info['height'];
							$new->store = $image_info['store'];
							$new->store_host = $image_info['store_host'];
							$new->save();
						} else {
							$this->errors['uploadError'] = $image->getError();
						}
						
						//extend form
						$forms = \Base\FormExtend::getExtension('pinForm.create');
						foreach($forms AS $form) {
							if($form->save) {
								$saveName = $form->save;
								new $saveName(array('pin_id'=>$pin_id, 'parent'=>$this,'type'=>'create'));
							}
						}
						//end extend form
					}

					if($this->errors) {
						$image->delete();
						$pinTable->getAdapter()->rollBack();
					} else {
						$pinTable->getAdapter()->commit();
						$url = $this->url(array('pin_id'=>$new->id,'query'=>$this->urlQuery($new->title)),'pin');
						@unlink(BASE_PATH . DIRECTORY_SEPARATOR . $data['pin_media']);
						
						$data['url'] = $url;
						$data['location'] = $url;
					}
				} catch (\Core\Exception $e) {
					$pinTable->getAdapter()->rollBack();
					$image->delete();
					$this->errors['saveData'] = $e->getMessage();
				}
	
			}
			if($this->errors) {
				$data['errors'] = $this->errors;
			}
			
		} else {
			$data['location'] = $this->url(array('controller' => 'login'),'user_c');
		}
		
		$this->responseJsonCallback($data);
	}

	private function validate() {
		$request = $this->getRequest();
		if($request->isPost()) {
			if( $request->getPost('X-form-cmd') == $this->x_form_cmd ) {
				$validator = new \Core\Form\Validator(array(
					'translate' => $this->_		
				));
				$validator->addNumber('category_id', array(
					'min' => 1,
					'error_text' => $this->_('You have to choose which category to pin to')
				));
				$validator->addText('title', array(
					'min' => 3,
					'max' => 250,
					'error_text_min' => $this->_('Title must contain more than %d characters')
				));
				$validator->addWysiwyg('description', array(
					'min' => 3,
					'error_text_min' => $this->_('Description must contain more than %d characters')
				));
				
				$forms = \Base\FormExtend::getExtension('pinForm.create');
				foreach($forms AS $form) {
					if($form->validator) {
						$validatorName = $form->validator;
						new $validatorName(array('validator'=>$validator, 'parent'=>$this,'type'=>'create'));
					}
				}
				
				if(!$validator->validate())
					$this->errors = $validator->getErrors();
			
				if(!$this->errors) {
					$checkImageMedia = new \Core\Image\Getimagesize($request->getPost('media'));

					if(	!is_array($info = $checkImageMedia->getSize())  ) {
						$this->errors['media'] = $this->_('Invalid image type!');
					} else {
						$allowed = $this->getAllowedTypes();
						if( !array_key_exists($info['mime'], $allowed) ) {
							$this->errors['media'] = $this->_('Image type is invalid!');
						} else {
							$config_image_minimum_size = (int)\Base\Config::get('config_image_minimum_size');
							if(!$config_image_minimum_size) { $config_image_minimum_size = 80; }
							if( min($info[0],$info[1]) >= $config_image_minimum_size ) {
								
							} else {
								$this->errors['media'] = sprintf($this->_('Photo size must be larger than width and height of %s px'), $config_image_minimum_size);
							}
						}
					}
				}
			} else {
				$this->errors['x-form-cmd'] = $this->_('Incorrect form data');
			}
			return $this->errors ? false : true;
		}
		return false;
	}
	
}