<?php

namespace Urlpin;

use \Base\Traits\FormInputPopulator;
use \Base\Traits\WysiwygFilter;

class EditController extends \Base\PermissionController {

	use FormInputPopulator;
	use WysiwygFilter;

	public $errors;
	
	public function init()
	{
		$request = $this->getRequest();
		
		if($request->isXmlHttpRequest())
			$this->noLayout(true);

		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction($data = [])
	{
		
		if(!isset($data['pin']) || !($data['pin'] instanceof \Pin\PinRow))
			$this->forward('error404');
		$request = $this->getRequest();
		$isXmlHttpRequest = $request->isXmlHttpRequest();
	
		$pinTable = $data['pin']->getTable();
		
		$this->x_form_cmd = $pinTable->getXFormCmd($data['pin']->id);

		$data['categories'] = \Category\Category::getCategoryTreeSelect();
		
		if($this->validate($data['pin'])) {
			//edit pin
			$editable = $pinTable->fetchRow(array('id = ?' => $data['pin']->id));
			$sourceTable = new \Source\Source();
// 			$editable->source_id = null;
// 			if($request->getRequest('from')) {
// 				$editable->source_id = $sourceTable->getSourceIdByLink($request->getRequest('from'));
// 			}
// 			$editable->from = '';
// 			if($editable->source_id) {
// 				$editable->from = $this->escape($request->getRequest('from'));
// 			}
			$editable->title = $this->escape($request->getRequest('title'));
			$editable->description = $this->filterWysiwyg($this->escape($request->getRequest('description')));
			$editable->category_id = (int) $request->getRequest('category_id');

			$pinTable->getAdapter()->beginTransaction();
			try {
				$editable->save();
				$type = $data['pin']->getType();
				$type = $type ? $type . '\Helper\Edit' : NULL;

				if($type && \Core\Loader\Loader::isLoadable($type) && class_exists($type))
					new $type([ 'pin_id' => $data['pin']->id, 'parent' => $this, 'type' => 'edit' ]);
						
				//extend form
				$forms = \Base\FormExtend::getExtension('pinForm.edit');
				foreach($forms AS $form)
				{
					if(!$form->save)
						continue;

					$saveName = $form->save;
					new $saveName([ 'pin_id' => $data['pin']->id, 'parent' => $this,'type' => 'edit' ]);
				}
				//end extend form

				$pinTable->getAdapter()->commit();
				$url = $this->url(array('pin_id'=>$editable->id, 'query' => $this->urlQuery($editable->title)),'pin');
				
				if($request->isXmlHttpRequest()) {
					$this->responseJsonCallback(array(
						'pin' => $url
					));
					exit;
				} else {
					$this->redirect($url);
				}
				
			} catch (\Core\Exception $e) {
				$pinTable->getAdapter()->rollBack();
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

	private function validate($pin) {
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
				$validator->addWysiwyg('description', array(
					'min' => 3,
					'error_text_min' => $this->_('Description must contain more than %d characters')
				));

				$type = $pin->getType();
				$type = $type ? $type . '\Helper\EditValidator' : NULL;

				if($type && \Core\Loader\Loader::isLoadable($type) && class_exists($type))
					new $type([ 'pin' => $pin, 'validator' => $validator, 'parent' => $this, 'type' => 'edit' ]);
				
				$forms = \Base\FormExtend::getExtension('pinForm.edit');
				foreach($forms AS $form) {
					if($form->validator) {
						$validatorName = $form->validator;
						new $validatorName(array('pin'=>$pin, 'validator'=>$validator, 'parent'=>$this,'type'=>'edit'));
					}
				}
				
				if(!$validator->validate())
					$this->errors = $validator->getErrors();

			} else {
				$this->errors['x-form-cmd'] = $this->_('Incorrect form data');
			}
			return $this->errors ? false : true;
		}
		return false;
	}
	
}