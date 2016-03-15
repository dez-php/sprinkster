<?php

namespace Category;

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
    	
		if(!$request->isXmlHttpRequest()) {
			$this->forward('error404');
		}
		
		$data = array();
		if($self->id) {
			$categoryTable = new \Category\Category();
			$category_info = $categoryTable->get($request->getRequest('category_id'));
			
			if($category_info) {
				try {
					$followHelper = new \Category\CategoryFollow();
					if(!$category_info->following_category) {
						$new = $followHelper->fetchNew();
						$new->category_id = $category_info->id;
						$new->user_id = $self->id;
						$new->date_added = \Core\Date::getInstance(null, \Core\Date::SQL_FULL, true)->toString();
						if($new->save()) {
							$data['isFollow'] = true;
						} else {
							$data['error'] = $this->_('There was a problem with the record. Please try again!');
						}
					} else {
						if($followHelper->delete(['user_id = ?' => $self->id,'category_id = ?' => $category_info->id])) {
							$data['isFollow'] = false;
						} else {
							$data['error'] = $this->_('There was a problem with the record. Please try again!');
						}
					}
					$data['info'] = $followHelper->statistic($category_info->id);
				} catch (\Core\Exception $e) {
					$data['error'] = $e->getMessage();
				}
			} else {
				$data['error'] = $this->_('There was a problem with the record. Please try again!');
			}
			
		} else {
			$data['popup'] = TRUE;
			$data['location'] = $this->url(array('controller' => 'login'),'user_c');
		}
		
		$this->responseJsonCallback( $data );
		
	}
	
	
}