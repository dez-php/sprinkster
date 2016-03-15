<?php

namespace Pin;

class DeleteController extends \Base\PermissionController {
	
	/**
	 * @var null|array
	 */
	public $errors;
	
	public function init() {
		$this->noLayout(true);
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() {
		$request = $this->getRequest();
		
		$data = array();
		
		$pin_id = $request->getRequest('pin_id');
		
		$userInfo = \User\User::getUserData();
		
		if(!$userInfo->id) {
			$this->forward('error404');
		}
		
		$pinTable = new \Pin\Pin();
		
		$data['pin'] = $pinTable->get($pin_id); 
		
		if(!$data['pin']) {
			$this->forward('error404');
		} else if($userInfo->is_admin ? false : $data['pin']->user_id != $userInfo->id ) {
			$data['errors']['nopermision'] = $this->_('There was a problem with the record. Please try again!');
		} else {
		
			//edit pin
// 			$editable = $pinTable->fetchRow(array('id = ?' => $data['pin']->id));
// 			$editable->status = 3;
			
			$pinTable->getAdapter()->beginTransaction();
			try {
				$demo_user_id = \Base\Config::get('demo_user_id');
				if($demo_user_id && $demo_user_id == \User\User::getUserData()->id) {
					 $data['errors']['saveData'] = $this->_('You don\'t have permissions for this action!');
				} else {
					$pinTable->extendDelete($data['pin']->id);
					$pinTable->getAdapter()->commit();
					\Base\Event::trigger('pin.delete',$pin_id);
					$data['location'] = $this->url(array('controller'=>'pin', 'user_id'=>$data['pin']->user_id,'query'=>$data['pin']->username),'user_c');
				}
			} catch (\Core\Exception $e) {
				$pinTable->getAdapter()->rollBack();
				$data['errors']['saveData'] = $e->getMessage();
			}

		}
		$this->responseJsonCallback($data);

	}
	
}