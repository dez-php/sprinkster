<?php

namespace Urlpin;

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
		$data = [ 'location' => false ];
		$me = \User\User::getUserData();

		if(!$me->id)
		{
			$url = $this->url([ 'controller' => 'login' ], 'user_c');
			return $request->isXmlHttpRequest() ? $this->responseJsonCallback([ 'location' => $url ])  : $this->redirect($url);
		}

		$pinTable = new \Pin\Pin();

		$this->x_form_cmd = $pinTable->getXFormCmd();
		
		$data['media'] = $request->getRequest('media');
		$data['title'] = $request->getRequest('title');
		$data['description'] = $this->filterWysiwyg($request->getRequest('description'));
		$data['url'] = $request->getRequest('url');
		$data['categories'] = \Category\Category::getCategoryTreeSelect();
		$wishlistTable = new \Wishlist\Wishlist([ \Wishlist\Wishlist::ORDER => 'sort_order ASC']);
        $data['wishlists'] = $wishlistTable->getWithShared($me->id);
		if( $this->validate() ) {
			
			//create pin
			$sourceTable = new \Source\Source();
			$new = $pinTable->fetchNew();
			$new->category_id = $request->getRequest('category_id');
			$new->user_id = $me->id;
			$new->source_id = $sourceTable->getSourceIdByLink($data['url']);
			$new->module = $this->getRequest()->getModule();
			
			$new->pinned_from = 'Pinned';
			
			if($new->source_id)
				$new->from = $data['url'];
			
			$new->title = $this->escape($request->getRequest('title'));
			$new->description = $this->filterWysiwyg($this->escape($data['description']));
			
			$pinTable->getAdapter()->beginTransaction();
			$image = \Base\Config::getUploadMethod('Base', 'pinThumbs');
			try
			{
				$pin_id = $new->save();
				if($pin_id) {
					if($request->getRequest('wishlist_id') && !empty($request->getRequest('wishlist_id'))){
						$repin = new \Pin\PinRepin;

			            $repin->delete([ 'user_id = ?' => $me->id, 'pin_id = ?' => $data['pin']->id]);

			            $new_repin = $repin->fetchNew();
			            $new_repin->wishlist_id = $request->getRequest('wishlist_id');
			            $new_repin->user_id = $me->id;
			            $new_repin->pin_id = $pin_id;
			            $new_repin->date_added = date('Y-m-d H:i:s');

			            $repin->getAdapter()->beginTransaction();

			            try {
			                $repin_id = $new_repin->save();

			                if (0 >= $repin_id) {
			                    $repin->getAdapter()->rollBack();
			                    throw new \Core\Exception('Repin Failed.');
			                }

			                $repin->getAdapter()->commit();

		                } catch (\Core\Exception $e) {
			                $pinTable->getAdapter()->rollBack();
							$image->delete();
							$this->errors['saveData'] = $e->getMessage();
			            }
			        }
					//upload image
					$image_path = '/pins' . \Core\Date::getInstance($new->date_added, '/yy/mm/', true);
					if( is_array($image_info = $image->upload($data['media'], $image_path)) ) {
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
							new $saveName(array('pin_id' => $pin_id, 'parent'=>$this,'type'=>'create'));
						}
					}
					//end extend form
				}
				if($this->errors) {
					$image->delete();
					$pinTable->getAdapter()->rollBack();
				} else {
					$pinTable->getAdapter()->commit();
					$url = $this->url([ 'pin_id' => $new->id,'query' => $this->urlQuery($new->title) ],'pin');

					$data['url'] = $url;

					return $request->isXmlHttpRequest() ? $this->responseJsonCallback([ 'location' => $url ]) : $this->redirect($url);
				}
			} catch (\Core\Exception $e) {
				$pinTable->getAdapter()->rollBack();
				$image->delete();
				$this->errors['saveData'] = $e->getMessage();
			}

		}
		if($this->errors) {
			if($request->isXmlHttpRequest()) {
				$this->responseJsonCallback(array('errors' => $this->errors));
				exit;
			}
		}
		
		$data['isXmlHttpRequest'] = $request->isXmlHttpRequest();
		
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
								$this->errors['media'] = sprintf($this->_('Photo size must be larger in width and height than %s px'), $config_image_minimum_size);
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