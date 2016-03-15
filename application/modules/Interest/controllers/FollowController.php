<?php

namespace Interest;

class FollowController extends \Base\PermissionController {
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() {
		
		$request = $this->getRequest();

    	//login popup
    	$self = \User\User::getUserData();
    	if (!$self->id)
    		$this->forward(
    				'popup', [
    					'url' => $this->url($request->getParams()),
    					'action' => 'follow-single'
    				],
    				'login',
    				'user');
    	//end login popup
		
		$request = $this->getRequest();
		if(!$request->isXmlHttpRequest()) {
			$this->forward('error404');
		}
		
		$data = array();
		if($self->id) {
			$interest_id = $request->getRequest('interest_id');
			$interestTable = new \Interest\Interest();
			$interest_info = $interestTable->fetchRow($interestTable->makeWhere(array('id'=>$interest_id)));
			
			if($interest_info) {
				$followTable = new \Interest\InterestFollow();
				try {
					if( $followTable->countByUserId_InterestId($self->id, $interest_id) ) {
						if($followTable->delete($followTable->makeWhere(array('user_id'=>$self->id,'interest_id'=>$interest_id)))) {
							$data['isFollow'] = false;
						} else {
							$data['error'] = $this->_('There was a problem with the record. Please try again!');
						}
					} else {
						$new = $followTable->fetchNew();
						$new->user_id = $self->id;
						$new->interest_id = $interest_id;
						if($new->save()) {
							$data['isFollow'] = true;
						} else {
							$data['error'] = $this->_('There was a problem with the record. Please try again!');
						}
					}
					$data['id'] = $data['info']['id'] = $interest_id;
					
					$data['info']['group'] = 'interest';
					$data['info']['stats']['followers'] = $interest_info->CountFollows();
				} catch (\Core\Db\Exception $e) {
					$data['error'] = $e->getMessage();
				}
			} else {
				$data['error'] = $this->_('There was a problem with the record. Please try again!');
			}
			
		} else {
			$data['location'] = $this->url(array('controller' => 'login'),'user_c');
		}
		
		$this->responseJsonCallback( $data );
		
	}
	
	
}