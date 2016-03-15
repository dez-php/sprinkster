<?php

namespace Facebook;

class RegisterController extends \Base\PermissionController {

	/**
	 * @var null|array
	 */
	public $errors;
	/**
	 * @var \Facebook\Helper\Me
	 */
	private $facebook;
	
	public function init() {
		if(!\Base\Config::get('facebook_status')) {
			$this->redirect( $this->url(array(),'welcome_home') );
		}
		if(\User\User::getUserData()->id) {
			$this->redirect( $this->url(array(),'welcome_home') );
		}
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
		$this->facebook = new \Facebook\Helper\Me();
	}

	public function indexAction() {
		$request = $this->getRequest();
		
		$user = $this->facebook->getUser();

		$invited = null;
		if($request->getRequest('invited_code')) {
			$invitedTable = new \Facebook\OauthFacebookInvite();
			$invited = $invitedTable->fetchRow(array('code = ?'=>$request->getRequest('invited_code')));
			$this->invited_code = $request->getRequest('invited_code');
		}
		
		if(!\Base\Config::get('open_registration') && (!$invited || !$invited->id)) {
			$this->redirect( $this->url(array('controller' => 'request'), 'invite_c') );
		}
		
		if($user) {
			$OauthFacebookTable = new \Facebook\OauthFacebook();
			
			try {
				$name = explode(' ', $user['name'], 2);
				$me = [
					'first_name' => $name[0],
					'last_name' => isset($name[1]) ? $name[1] : '',
					'email' => isset($user['email'])?$user['email']:'',
					'username' => array_shift(explode('@', isset($user['email'])?$user['email']:''))
				];
				if(!$request->isPost()) {
					$request->setPost($me);
				}
				$this->access_token = $user['accessToken'];
				
				$userTable = new \User\User();
				$this->x_form_cmd = $userTable->getXFormCmd();
				
				if($request->isPost()) {
					//check if exist oauth
					$userOauth = $OauthFacebookTable->fetchRow(array('facebook_id = ?' => $user['id']));

					if($userOauth) {
						if( $userTable->loginById($userOauth->user_id) ) {
							$userOauth->access_token = $user['accessToken'];
							$userOauth->save();
							$this->facebook->setLongAccessToken();
							// Added strpos check to pass McAfee PCI compliance test
							$redirect = $request->getRequest('next');
							if($redirect && strpos($redirect, $request->getBaseUrl()) !== false) {
								$this->redirect( str_replace('&amp;', '&', $redirect) );
							} else {
								$this->redirect( $this->url(array(),'welcome_home') );
							}
						}
					} else {

						if($this->validateRegister()) {
							$userTable->getAdapter()->beginTransaction();
							try {
								$new = $userTable->fetchNew();
								$new->username = $request->getPost('username');
								$new->email = $request->getPost('email');
								$new->password = md5($request->getPost('password'));
								$new->firstname = $request->getPost('first_name');
								$new->lastname = $request->getPost('last_name');
								$new->status = (int)\Base\Config::get('register_user_status');
								$new->gender = $request->getPost('gender') ? $request->getPost('gender') : (isset($me['gender']) && in_array($me['gender'], ['male','female','unsigned']) ? $me['gender'] : 'unsigned');
								
								if($new->save()) {
									//avatar
									$avatar = \Facebook\Helper\Photo::getRealUrl("https://graph.facebook.com/me/picture?access_token=".$this->access_token);
									$image_path = '/users' . \Core\Date::getInstance($new->date_added, '/yy/mm/', true);
									$image = \Base\Config::getUploadMethod('Base', 'userAvatars');
									if($avatar) {
										$name = mt_rand() .'_'.mt_rand() . '.jpg';
										$img = @imagecreatefromstring(@file_get_contents($avatar));
										if($img && @imagejpeg($img, BASE_PATH . '/cache/tmp/' . $name, 100)) {
											$avatar = BASE_PATH . '/cache/tmp/' . $name;
										} else {
											$avatar = BASE_PATH . '/cache/tmp/' . $name;
										}
									}
									if( $avatar && is_array($image_info = $image->upload($avatar, $image_path)) ) {
										$new->avatar = $image_info['file'];
										$new->avatar_width = $image_info['width'];
										$new->avatar_height = $image_info['height'];
										$new->avatar_store = $image_info['store'];
										$new->avatar_store_host = $image_info['store_host'];
										$new->save();
									}
									$newfb = $OauthFacebookTable->fetchNew();
									$newfb->user_id = $new->id;
									$newfb->facebook_id = $user['id'];
									$newfb->access_token = $this->access_token;
									$newfb->date_added = $new->date_added;
									$newfb->date_modified = $new->date_added;
									if($newfb->save()) {
					
										//extend form
										$forms = \Base\FormExtend::getExtension('userForm.register.facebook');
										foreach($forms AS $form) {
											if($form->save) {
												$saveName = $form->save;
												new $saveName(array('user_id'=>$new->id, 'parent'=>$this,'type'=>'register'));
											}
										}
										//end extend form
										if($invited && $invited->id) {
											//follow all invites
											$invitedTable = new \Facebook\OauthFacebookInvite();
											$invitedRows = $invitedTable->fetchAll(array('facebook_id = ?'=>$user));
											foreach($invitedRows AS $row) {
												$followHelper = new \User\Helper\Follow($newfb->user_id,$row->user_id);
												if(!$user_info->following_user) {
													$followHelper->followUser();
												}
												$followHelper = new \User\Helper\Follow($row->user_id,$newfb->user_id);
												if(!$user_info->following_user) {
													$followHelper->followUser();
												}
												$row->delete();
											}
						
											//statistic for invitation
											$userInviteTable = new \User\UserInvite();
											$userInvite = $userInviteTable->fetchNew();
											$userInvite->user_id = $invited->user_id;
											$userInvite->invite_id = $new->id;
											$userInvite->save();
										}
					
										////////////// notification
										$NotificationTable = new \Notification\Notification();
										$Notification = $NotificationTable->setReplace(array(
												'user_id' => $new->id,
												'user_firstname' => $new->firstname,
												'user_lastname' => $new->lastname,
												'user_fullname' => $new->getUserFullname(),
										))->get('welcome');
										
										if($Notification) {
											$email = new \Helper\Email();
											$email->addFrom(\Base\Config::get('no_reply'));
											$email->addTo($new->email, $new->getUserFullname());
											$email->addTitle($Notification->title);
											$email->addHtml($Notification->description);
											$email->send();
										}
										/////////////////////////
										
										$userTable->getAdapter()->commit();
										//login
										if( $userTable->loginById($new->id) ) {
											// Added strpos check to pass McAfee PCI compliance test
											$redirect = $request->getRequest('next');
											if($redirect && strpos($redirect, $request->getBaseUrl()) !== false) {
												$this->redirect( str_replace('&amp;', '&', $redirect) );
											} else {
												$this->redirect( $this->url(array(),'welcome_home') );
											}
										}
										if(!$this->errors && ($error = $userTable->getErrors()) !== null ) {
											if($error == \User\User::USER_NOT_FOUND) {
												$this->errors['notfound'] = $this->_('User not found');
											} else if($error == \User\User::USER_NOT_ACTIVE) {
												$this->redirect( $this->url(array('controller'=>'status','user_id' => $new->id),'user_c') );
											}
										}
									} else {
										$userTable->getAdapter()->rollBack();
										$this->errors['errorUserNewFb'] = $this->_('There was a problem with the record. Please try again!');
									}
									
								} else {
									$userTable->getAdapter()->rollBack();
									$this->errors['errorUserNew'] = $this->_('There was a problem with the record. Please try again!');
								}
							} catch (\Core\Exception $e) {
								$userTable->getAdapter()->rollBack();
								$this->errors['Exception'] = $e->getMessage();
							}
						}
						
					}
				}
				
			} catch (\Core\Exception $e) {
				// not allow
				$this->forward('index', array(), 'not-allowed');
			}
			
			//call to user/register view
			$this->render('index', null, ['module' => 'user','controller' => 'register', 'action' => 'index']);
			
		} else {
			// not allow
			$this->forward('index', array(), 'not-allowed');
		}
		
	}

	private function validateRegister() {
		$request = $this->getRequest();
		if($request->isPost()) {
			if( $request->getPost('X-form-cmd') == $this->x_form_cmd ) {
				$validator = new \Core\Form\Validator(array(
					'translate' => $this->_		
				));
				$validator->addEmail('email');
                $validator->addPassword('password', array(
                    'min' => 3,
                    'error_text_min' => $this->_('Password must contain more than %d characters')
                ));
                $validator->addUsername('username');
				
				$forms = \Base\FormExtend::getExtension('userForm.register.facebook');
				foreach($forms AS $form) {
					if($form->validator) {
						$validatorName = $form->validator;
						new $validatorName(array('validator'=>$validator, 'parent'=>$this,'type'=>'register'));
					}
				}
                
                if($request->getPost('password') != $request->getPost('repass')) {
                    $this->errors['repass'] = $this->_('Passwords did not match.');
                }
                
				if($validator->validate()) {
					$userTable = new \User\User();
					// check username if exist
					if($userTable->countByUsername($request->getPost('username'))) {
						$this->errors['username'] = $this->_('This username is already being used');	
					} else if(in_array(strtolower($request->getPost('username')), (new \User\User())->getReserved())) {
						$this->errors['username'] = $this->_('This username is already being used');
					}
					// check username if exist
					if($userTable->countByEmail($request->getPost('email'))) {
						$this->errors['email'] = $this->_('This email address is already being used');	
					}
				} else {
					$this->errors = array_merge($this->errors?$this->errors:[], $validator->getErrors());
				}
			} else {
				$this->errors['x-form-cmd'] = $this->_('Incorrect form data');
			}
			return $this->errors ? false : true;
		}
		return false;
	}
	
}