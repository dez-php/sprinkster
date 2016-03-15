<?php

namespace Wishlist;

class EditController extends \Base\PermissionController {
	
	private $errors = array();
	
	public function init() {
		$request = $this->getRequest();
		if($request->isXmlHttpRequest()) {
			$this->noLayout(true);
		}
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function indexAction() {
		$request = $this->getRequest();
		$data = [ 'location' => false ];
		$userInfo = \User\User::getUserData();

		if(!$userInfo->id)
			$data['location'] = $this->url(array('controller' => 'login'),'user_c');
		
		$wishlistTable = new \Wishlist\Wishlist();
		$this->x_form_cmd = $wishlistTable->getXFormCmd();
		
		$wishlist = $wishlistTable->fetchRow(array('id = ?' => $request->getRequest('wishlist_id')));
		if(!$wishlist) {
			$this->forward('error404');
		} else if($userInfo->is_admin ? false : $wishlist->user_id != $userInfo->id ) {
			$this->forward('error404');
		}
		
		$userTable = new \User\User();
		$wishlistShareTable = new \Wishlist\WishlistShare();
		
		$data['isXmlHttpRequest'] = $request->isXmlHttpRequest();
		$data['user'] = $userTable->fetchRow([ 'id = ?' => $wishlist->user_id ]);

		if($request->isPost() && $this->validate()) {
			$wishlistTable->getAdapter()->beginTransaction();
			try {
				$wishlist->title = $this->escape($request->getPost('title'));
				$wishlist->description = $this->escape($request->getPost('description'));
				$wishlist->email_me = 1;//$this->escape($request->getPost('email_me'));
				
				if(isset($wishlist->secret))
					$wishlist->secret = (int) $request->getPost('secret');

				if($wishlist->save()) {
// 					$data['user']->wishlists = $wishlistTable->countByUserId_Status($data['user']->id,1);
// 					$data['user']->save();
					$invite = $request->getPost('invite');
					if($invite) {
						$allInvite = $wishlistShareTable->fetchAll(array('wishlist_id = ?' => $wishlist->id));
						$deleted = $inserted = array();
						if($allInvite) {
							foreach($allInvite AS $all) {
								$inarray = array_search($all->share_id, $invite);
								if($inarray === false) {
									$deleted[] = $all->share_id;
								} else {
									unset($invite[$inarray]);
								}
							}
						}
						
						if($deleted) {
							$wishlistShareTable->delete($wishlistShareTable->makeWhere(array('wishlist_id' => $wishlist->id, 'share_id' => $deleted)));
							foreach($deleted AS $uid) {
								//remove activity
								(new \Pin\PinRepin())->delete(['wishlist_id = ?' => $wishlist->id, 'user_id = ?' => $uid]);
								\Activity\Activity::remove($uid, 'INVITEWISHLIST',null,$wishlist->id);
							}
						}
						
						if($invite) {
							$users = $userTable->fetchAll($userTable->makeWhere(array('id' => $invite)));
							///////share
							///////notify
							$NotificationTable = new \Notification\Notification();
							////////
							foreach($users AS $user) {
								$newShare = $wishlistShareTable->fetchNew();
								$newShare->wishlist_id = $wishlist->id;
								$newShare->user_id = $userInfo->id;
								$newShare->share_id = $user->id;
								$newShare->date_added = $wishlist->date_added;
								if($newShare->save()) {
									//send notifikation
									$NotificationTable = new \Notification\Notification();
									$NotificationTable->send('wishlist_invite', [
										'user_id' => $user->id,
										'user_username' => $user->username,
										'user_firstname' => $user->firstname,
										'user_lastname' => $user->lastname,
										'user_username' => $user->username,
										'user_fullname' => $user->getUserFullname(),
										'wishlist_url' => $this->url(array('wishlist_id' => $wishlist->id, 'query' => $this->urlQuery($wishlist->title)), 'wishlist'),
										'wishlist_name' => $wishlist->title,
										'author_url' => $this->url(array('user_id' => $userInfo->id, 'query' => $userInfo->username), 'user'),
										'author_fullname' => $userInfo->getUserFullname(),
											 
										'language_id' => $user->language_id,
										'email' => $user->email,
										'fullname' => $user->getUserFullname(),
										'notify' => 1
									]);
									///////////////// end send
								}
								//add activity
								\Activity\Activity::set($user->id, 'INVITEWISHLIST',null,$wishlist->id);
							}
						}
					} else {
						$allInvite = $wishlistShareTable->fetchAll(array('wishlist_id = ?' => $wishlist->id));
						if($allInvite) {
							foreach($allInvite AS $all) {
								//remove activity
								(new \Pin\PinRepin())->delete(['wishlist_id = ?' => $wishlist->id, 'user_id = ?' => $all->share_id]);
								\Activity\Activity::remove($all->share_id, 'INVITEWISHLIST',null,$wishlist->id);
							}
						}
						$wishlistShareTable->delete(array('wishlist_id = ?' => $wishlist->id));
					}
						
					//extend form
					$forms = \Base\FormExtend::getExtension('wishlistForm.edit');
					foreach($forms AS $form) {
						if($form->save) {
							$saveName = $form->save;
							new $saveName(array('wishlist_id'=>$wishlist->id, 'parent'=>$this,'type'=>'edit'));
						}
					}
					//end extend form
						
					$wishlistTable->getAdapter()->commit();
					$url = $this->url(array('wishlist_id' => $wishlist->id,'query'=>$this->urlQuery($wishlist->title)),'wishlist');
					if($data['isXmlHttpRequest']) {
						$this->responseJsonCallback(array('location' => $url));
						exit;
					} else {
						$this->redirect($url);
					}
				} else {
					$wishlistTable->getAdapter()->rollBack();
					$this->errors['newRecord'] = $this->_('There was a problem with the record. Please try again!');
				}
			} catch ( \Core\Exception $e ) {
				$wishlistTable->getAdapter()->rollBack();
				$this->errors['Exception'] = $e->getMessage();
			}
			
		}
		if($data['isXmlHttpRequest'] && $this->errors) {
			$this->responseJsonCallback(array('errors' => $this->errors, 'location' => $data['location']));
			exit;
		}
		
		$data['users'] =  $userTable->fetchAll($userTable->makeWhere(array('id' => array($wishlistShareTable->select()->from($wishlistShareTable,'share_id')->where('wishlist_id = ?', $wishlist->id)))));
		
		$data['wishlist'] = $wishlist;
		
		
		$this->render('index', $data);
	}

	private function validate() {
		$request = $this->getRequest();
		if($request->isPost()) {
			if( $request->getPost('X-form-cmd') == $this->x_form_cmd ) {
				$validator = new \Core\Form\Validator(array(
					'translate' => $this->_		
				));
				
				$validator->addText('title', array(
					'min' => 3,
					'error_text_min' => $this->_('Title must contain more than %d characters')
				));
				
				$forms = \Base\FormExtend::getExtension('wishlistForm.create');
				foreach($forms AS $form) {
					if($form->validator) {
						$validatorName = $form->validator;
						new $validatorName(array('validator'=>$validator, 'parent'=>$this,'type'=>'edit'));
					}
				}
				
				if($validator->validate()) {
					return true;
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