<?php

namespace Bookmarklet;

class CreatePinController extends \Base\PermissionController {
	
	use \Local\Traits\Allow;
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() { 
		$request = $this->getRequest();
		$userInfo = \User\User::getUserData();
		if(!$userInfo->id) {
			\Core\Session\Base::set('redirect', $request->getFullUrl());
			$this->redirect( $this->url(array('controller' => 'login','query'=>'?popup=true'),'user_c',false,false) );
		}
		
		$pinTable = new \Pin\Pin();
		
		$this->x_form_cmd = $pinTable->getXFormCmd();
			
		if(strpos($request->getRequest('media'), 'data:image/') === false) {
			$helperImage = new \Urlpin\Helper\Element($request->getRequest('media'));
			$getedImages = $helperImage->getAdapter()->result();
			if(isset($getedImages['images']) && $getedImages['images']) {
				$image = array_shift($getedImages['images']);
				$data['pin_media'] = $image['image'];
			} else {
				$data['pin_media'] = $request->getRequest('media');
			}
		} else {
			$guid = $this->guid(md5($request->getRequest('media')));
			\Core\Session\Base::set(__NAMESPACE__ . $guid, $request->getRequest('media'));
			$data['pin_media'] = $this->url(['controller' => 'image', 'guid' => $guid], 'bookmarklet_guid');
		}
		
		$data['charset'] = $request->getRequest('charset');
		if($request->issetPost('from_grid')) {
			$data['pin_title'] = \Core\Utf8::convert($request->getRequest('title'), $data['charset'], 'utf-8');
			$data['pin_description'] = \Core\Utf8::convert($request->getRequest('description'), $data['charset'], 'utf-8');
		} else {
			$data['pin_title'] = $request->getRequest('title');
			$data['pin_description'] = $request->getRequest('description');
		}
		
		$data['pin_url'] = $request->getRequest('url');
		$data['pin_category_id'] = $request->getRequest('category_id');
		
		if(!$request->issetPost('from_grid') && $this->validate()) {
		
			$cleared = null;
			//create pin
			$sourceTable = new \Source\Source();
			$new = $pinTable->fetchNew();
			$new->user_id = $userInfo->id;
			$new->source_id = $sourceTable->getSourceIdByLink($data['pin_url']);
			$new->pinned_from = 'Pinned';
			$new->module = 'Urlpin';
			if($new->source_id) {
				$new->from = $data['pin_url'];
			}
			//$new->title = $data['pin']->title;
			//$new->video = 0;
			$new->title = $this->escape($data['pin_title']);
			$new->description = $this->filterWysiwyg($this->escape($data['pin_description']));
			$new->category_id = (int)$data['pin_category_id'];
		
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
					$url = $this->url(array('controller'=>'share'),'bookmarklet');
					\Core\Session\Base::set('bookmarklet_pin',$new->toArray());
					if($request->isXmlHttpRequest()) {
						$this->responseJsonCallback(array(
								'location' => $url
						));
						exit;
					} else {
						$this->redirect($url);
					}
				}
			} catch (\Core\Exception $e) {
				$pinTable->getAdapter()->rollBack();
				$image->delete();
				$this->errors['saveData'] = $e->getMessage();
			}
		
		}
	
		$data['categories'] = \Category\Category::getCategoryTreeSelect();
		
		$this->render('index', $data);
	}

	private function validate() {
		$request = $this->getRequest();
		if($request->isPost()) {
			if( $request->getPost('X-form-cmd') == $this->x_form_cmd ) {
				$validator = new \Core\Form\Validator(array(
					'translate' => $this->_		
				));
				$validator->addNumber('category_id', array(
					'min' => 0,
					'error_text' => $this->_('You have to choose which category to pin to')
				));
				$validator->addText('title', array(
					'min' => 3,
					'max' => 250,
					'error_text_min' => $this->_('Title must contain more than %d characters')
				));
				$validator->addText('description', array(
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
				if($validator->validate()) {
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
								$this->errors['media'] = sprintf($this->_('Photo size must be bigger in width and height than %s px'), $config_image_minimum_size);
							}
						}
					}
				} else {
					$this->errors = $validator->getErrors();
				}
			} else {
				$this->errors['x-form-cmd'] = $this->_('Incorrect form data');
			}
			return $this->errors ? false : true;
		}
		return false;
	}
	
	
	
	
	
}