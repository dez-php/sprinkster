<?php

namespace Pin;

class RepinController extends \Base\PermissionController {

    /**
     * @var null|array
     */
    public $errors;

    public function init() {
        $request = $this->getRequest();
        if ($request->isXmlHttpRequest()) {
            $this->noLayout(true);
        }
        $this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
    }

    public function indexAction() {
    	
    	$request = $this->getRequest();

    	//login popup
    	$userInfo = \User\User::getUserData();
    	if (!$userInfo->id)
    		$this->forward(
    				'popup', [
    					'url' => $this->url($request->getParams()),
    					'action' => 'popup'
    				],
    				'login',
    				'user');
    	//end login popup

        $data = array();

        $pin_id = $request->getRequest('pin_id');

        $pinTable = new \Pin\Pin();

        $data['pin'] = $pinTable->get($pin_id);

        if (!$data['pin'])
            $this->forward('error404');

        // Disable repinning of own items
        if($userInfo->id === $data['pin']->user_id)
            $this->forward('error404');
        
        if($data['pin']->in_wishlist)
        	$this->forward('remove', $data);

        $this->x_form_cmd = $pinTable->getXFormCmd($pin_id);

        if ($this->validate()) {
            //create repin
            $repin = new \Pin\PinRepin;

            $repin->delete([ 'user_id = ?' => $userInfo->id, 'pin_id = ?' => $data['pin']->id]);

            $new = $repin->fetchNew();
            $new->wishlist_id = $request->getRequest('wishlist_id');
            $new->user_id = $userInfo->id;
            $new->pin_id = $data['pin']->id;
            $new->date_added = date('Y-m-d H:i:s');

            $repin->getAdapter()->beginTransaction();

            try {
                $repin_id = $new->save();

                if (0 >= $repin_id) {
                    $repin->getAdapter()->rollBack();
                    throw new \Core\Exception('Repin Failed.');
                }

                $repin->getAdapter()->commit();

                $wishlist = (new \Wishlist\Wishlist())->get($new->wishlist_id);
                
                $url = $this->url([ 'pin_id' => $data['pin']->id], 'pin');
                $url_wish = $this->url([ 'wishlist_id' => $new->wishlist_id, 'query' => $this->urlQuery($wishlist->title)], 'wishlist');

                \Activity\Activity::set($data['pin']->user_id, 'REPIN', $data['pin']->id, $new->wishlist_id);

                $userTable = new \User\User();
                $authorInfo = $userTable->fetchRow($userTable->makeWhere([ 'id' => $data['pin']->user_id]));

                // Send Notification
                if ($authorInfo && $authorInfo->notification_repin_pin && $authorInfo->id != $authorInfo->id) {
                    //send notifikation
                    $NotificationTable = new \Notification\Notification();
                    $NotificationTable->send('add_to_wishlist', [
                    	'user_id' => $authorInfo->id,
						'user_firstname' => $authorInfo->firstname,
                        'user_lastname' => $authorInfo->lastname,
                        'user_username' => $authorInfo->username,
                        'user_fullname' => $authorInfo->getUserFullname(),
                        'pin_url' => $this->url([ 'pin_id' => $data['pin']->id], 'pin'),
                        'author_url' => $this->url([ 'user_id' => $userInfo->id, 'query' => $userInfo->username], 'user'),
                        'author_fullname' => $userInfo->getUserFullname(),
                    			
                    	'language_id' => $authorInfo->language_id,
                    	'email' => $authorInfo->email,
                    	'fullname' => $authorInfo->getUserFullname(),
                    	'notify' => $authorInfo->notification_repin_pin
					]);
                    
                }
                
                // Send Shared Wishlist Notification
                /*$wishlistTable = new \Wishlist\Wishlist();
                $wishlist_info = $wishlistTable->getWithShared(\User\User::getUserData()->id, $new->wishlist_id);
                if ($wishlist_info->count() && $wishlist_info->offsetGet(0)->user_id != \User\User::getUserData()->id) {
                    $userTable = new \User\User();
                    $user_info = $userTable->fetchRow($userTable->makeWhere([ 'id' => $wishlist_info->offsetGet(0)->user_id]));
                    if ($user_info && $user_info->notification_group_wishlist) {
                        $self_data = \User\User::getUserData();

                        //send notifikation
                        $NotificationTable = new \Notification\Notification();
                        $NotificationTable->send('group_wishlist', [
							'user_id' => $user_info->id,
                            'user_firstname' => $user_info->firstname,
                            'user_lastname' => $user_info->lastname,
                            'user_username' => $user_info->username,
                            'user_fullname' => $user_info->getUserFullname(),
                            'wishlist_url' => $this->url([ 'wishlist_id' => $new->wishlist_id, 'query' => $this->urlQuery($wishlist_info->offsetGet(0)->title)], 'wishlist'),
                            'wishlist_name' => $wishlist_info->offsetGet(0)->title,
                            'pin_url' => $this->url([ 'pin_id' => $new->pin_id], 'pin'),
                            'author_url' => $this->url([ 'user_id' => $self_data->id, 'query' => $self_data->username], 'user'),
                            'author_fullname' => $self_data->getUserFullname(),
                        		 
                        	'language_id' => $user_info->language_id,
                        	'email' => $user_info->email,
                        	'fullname' => $user_info->getUserFullname(),
                        	'notify' => $user_info->notification_group_wishlist
						]);
                        
                    }
                }*/
                $wishlistSharedTable = new \Wishlist\WishlistShare();
                $wishlists = $wishlistSharedTable->fetchAll($wishlistSharedTable->makeWhere(['wishlist_id' => $new->wishlist_id,'accept' => 1]));
                if($wishlists->count()) {
                	$self_data = \User\User::getUserData();
                	$userTable = new \User\User();
                	$wishlist_info = $wishlists->offsetGet(0)->Wishlist();
                	if($wishlist_info) {
	                	foreach($wishlists AS $wsh) {
	                		$user_info = $wsh->Share();
	                		if($user_info && $user_info->notification_group_wishlist) {
	                			//send notifikation
	                			$NotificationTable = new \Notification\Notification();
	                			$NotificationTable->send('group_wishlist', [
	                					'user_id' => $user_info->id,
	                					'user_firstname' => $user_info->firstname,
	                					'user_lastname' => $user_info->lastname,
	                					'user_username' => $user_info->username,
	                					'user_fullname' => $user_info->getUserFullname(),
	                					'wishlist_url' => $this->url([ 'wishlist_id' => $new->wishlist_id, 'query' => $this->urlQuery($wishlist_info->title)], 'wishlist'),
	                					'wishlist_name' => $wishlist_info->title,
	                					'pin_url' => $this->url([ 'pin_id' => $new->pin_id], 'pin'),
	                					'author_url' => $this->url([ 'user_id' => $self_data->id, 'query' => $self_data->username], 'user'),
	                					'author_fullname' => $self_data->getUserFullname(),
	                					'language_id' => $user_info->language_id,
	                					'email' => $user_info->email,
	                					'fullname' => $user_info->getUserFullname(),
	                					'notify' => $user_info->notification_group_wishlist
	                			]);
	                		}
	                	}
                	}
                }

                return $request->isXmlHttpRequest() ? $this->responseJsonCallback([ 'pin' => $url_wish, 'repin' => \Pin\Pin::getInfo($data['pin']->id)]) : $this->redirect($url_wish);
            } catch (\Core\Exception $e) {
                $pinTable->getAdapter()->rollBack();
                $this->errors['saveData'] = $e->getMessage();
            }
        }

        if ($this->errors && $request->isXmlHttpRequest()) {
            $this->responseJsonCallback(array('errors' => $this->errors));
            exit;
        }

        if ($data['pin']['gallery']) {
            $galleryTable = new \Pin\PinGallery();
            $data['gallery'] = $galleryTable->fetchAll([ 'pin_id = ?' => $data['pin']->id]);
        }

        $data['isXmlHttpRequest'] = $request->isXmlHttpRequest();

        //get user wishlists
        $wishlistTable = new \Wishlist\Wishlist([ \Wishlist\Wishlist::ORDER => 'sort_order ASC']);
        $data['wishlists'] = $wishlistTable->getWithShared($userInfo->id);

        if ($request->isXmlHttpRequest() && $request->getQuery('callback')) {
            $this->responseJsonCallback(array(
                'content' => $this->render('index', $data, null, true),
                'title' => $this->_('Repin')
            ));
        } else {
            $this->render('index', $data);
        }
    }
    
    public function removeAction($data = []) {
    	$request = $this->getRequest();
    	if(!$data || !isset($data['pin']))
    		$this->forward('error404');

        $userInfo = \User\User::getUserData();
    	
    	$data['isXmlHttpRequest'] = $request->isXmlHttpRequest();
    	
    	$repin = new \Pin\PinRepin;
    	$wishlist = $repin->fetchRow([ 'user_id = ?' => $userInfo->id, 'pin_id = ?' => $data['pin']->id]);
    	if(!$wishlist)
    		$this->forward('error404');
    	$wishlist_id = $wishlist->wishlist_id;
    	$data['wishlist'] = $wishlistData = (new \Wishlist\Wishlist())->get($wishlist_id);
    	if(!$wishlistData)
    		$this->forward('error404');
    	
    	if($this->validateDelete()) {
	    	try {
	    		if(!$wishlist->delete())
	    			throw new \Core\Exception('Remove from Collection failed.');
	    			
	    		\Activity\Activity::set($data['pin']->user_id, 'UNREPIN', $data['pin']->id, $wishlist_id);
	    		
	    		$url = $this->url([ 'pin_id' => $data['pin']->id], 'pin');
	    		
	    		$url_wish = $this->url([ 'wishlist_id' => $wishlistData->id, 'query' => $this->urlQuery($wishlistData->title)], 'wishlist');
	    		return $request->isXmlHttpRequest() ? $this->responseJsonCallback([ 'pin' => $url_wish, 'repin' => \Pin\Pin::getInfo($data['pin']->id)]) : $this->redirect($url_wish);
	    		
	    	} catch (\Core\Exception $e) {
	    		$this->errors['saveData'] = $e->getMessage();
	    	}
    	
    	}

        if ($this->errors && $request->isXmlHttpRequest()) {
            $this->responseJsonCallback(array('errors' => $this->errors));
            exit;
        }
    	
    	if ($request->isXmlHttpRequest() && $request->getQuery('callback')) {
    		$this->responseJsonCallback(array(
    				'content' => $this->render('remove', $data, null, true),
    				'title' => $this->_('Repin')
    		));
    	} else {
    		$this->render('remove', $data);
    	}
    }

    private function validate() {
        $request = $this->getRequest();
        if ($request->isPost()) {
                $validator = new \Core\Form\Validator(array(
                    'translate' => $this->_
                ));
                $validator->addNumber('wishlist_id', array(
                    'min' => 0,
                    'error_text' => $this->_('You have to choose a Collection first')
                ));

                if ($validator->validate()) {
                    $wishlistTable = new \Wishlist\Wishlist();
                    if ($wishlistTable->getWithShared(\User\User::getUserData()->id, $request->getPost('wishlist_id'))->count()) {
                        return true;
                    } else {
                        $this->errors['wishlist_id'] = $this->_('You do not have permission to pin to this Collection');
                    }
                } else {
                    $this->errors = $validator->getErrors();
                }
        }
        return false;
    }

    private function validateDelete() {
        $request = $this->getRequest();
//         if ($request->isPost()) {
//             if ($request->getPost('X-form-cmd') == $this->x_form_cmd) {
// 				return true;
//             } else {
//                 $this->errors['x-form-cmd'] = $this->_('Incorrect form data');
//             }
//         }
        return $request->isPost();
    }

}
