<?php

namespace User;

class RegisterController extends \Core\Base\Action {

    /**
     * @var null|array
     */
    public $errors;

    public function init() {
        if (\User\User::getUserData()->id) {
            $this->redirect($this->url(array(), 'welcome_home'));
        }
        $request = $this->getRequest();
        if ($request->getQuery('next') && strpos($request->getQuery('next'), $request->getBaseUrl()) !== false) {
            \Core\Session\Base::set('redirect', $request->getQuery('next'));
            $this->redirect($this->url(array('controller' => 'login'), 'user_c'));
        }

        if ($request->isXmlHttpRequest()) {
            $this->noLayout(true);
        }
        $this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
    }

    public function indexAction() {
        $request = $this->getRequest();
        $invited = null;
        if ($request->getRequest('invited_code') && strlen($request->getRequest('invited_code')) == 32) {
            $invitedTable = new \Invite\Invite();
            $invited = $invitedTable->fetchRow(array('code = ?' => $request->getRequest('invited_code')));
            $this->invited_code = $request->getRequest('invited_code');
        }

        if (!\Base\Config::get('open_registration') && (!$invited || !$invited->id)) {
            $url = $this->url(array('controller' => 'request'), 'invite_c');
            if ($request->isXmlHttpRequest()) {
                $url .= '?' . http_build_query($_GET);
            }
            $this->redirect($url);
        }

        $userTable = new \User\User();

        $this->x_form_cmd = $userTable->getXFormCmd();

        if ($this->validateRegister()) {
            $userTable->getAdapter()->beginTransaction();
            try {
                $new = $userTable->fetchNew();
                $new->username = $request->getPost('username');
                $new->email = $request->getPost('email');
                $new->password = md5($request->getPost('password'));
                $new->firstname = $request->getPost('first_name');
                $new->lastname = $request->getPost('last_name');
                $new->status = (int) \Base\Config::get('register_user_status');
                //$new->gender = $request->getPost('gender');

                if ($new->save()) {
                    //extend form
                    $forms = \Base\FormExtend::getExtension('userForm.register');
                    foreach ($forms AS $form) {
                        if ($form->save) {
                            $saveName = $form->save;
                            new $saveName(array('user_id' => $new->id, 'parent' => $this, 'type' => 'register'));
                        }
                    }
                    //end extend form

                    if ($invited && $invited->id && $invited->user_id) {
                        //follow all invites
                        $user_info = $invited->user_id ? $userTable->get($invited->user_id) : false;
                        if ($invited->user_id) {
                            $followHelper = new \User\Helper\Follow($new->id, $invited->user_id);
                            if ($user_info && !$user_info->following_user) {
                                $followHelper->followUser();
                            }
                            $followHelper = new \User\Helper\Follow($invited->user_id, $new->id);
                            if ($user_info && !$user_info->following_user) {
                                $followHelper->followUser();
                            }
                        }
                        $invited->delete();

                        if ($invited->user_id) {
                            //statistic for invitation
                            $userInviteTable = new \User\UserInvite();
                            $userInvite = $userInviteTable->fetchNew();
                            $userInvite->user_id = $invited->user_id;
                            $userInvite->invite_id = $new->id;
                            $userInvite->save();
                        }
                    }

                    ////////////// notification
                    $NotificationTable = new \Notification\Notification();
                    $Notification = $NotificationTable->setReplace(array(
                                'user_id' => $new->id,
                                'user_firstname' => $new->firstname,
                                'user_lastname' => $new->lastname,
                                'user_fullname' => $new->getUserFullname(),
                            ))->get('welcome');

                    if ($Notification) {
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
                    if ($userTable->loginById($new->id)) {
                        // Added strpos check to pass McAfee PCI compliance test
                        $redirect = $request->getRequest('redirect');
                        if ($redirect && strpos($redirect, $request->getBaseUrl()) !== false) {
                            $this->redirect(str_replace('&amp;', '&', $redirect));
                        } else {
                            $this->redirect($this->url(array(), 'welcome_home'));
                        }
                    }
                    if (!$this->errors && ($error = $userTable->getErrors()) !== null) {
                        if ($error == \User\User::USER_NOT_FOUND) {
                            $this->errors['notfound'] = $this->_('User not found');
                        } else if ($error == \User\User::USER_NOT_ACTIVE) {
                            $this->redirect($this->url(array('controller' => 'status', 'user_id' => $new->id), 'user_c'));
                        }
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

        // Added strpos check to pass McAfee PCI compliance test
        if ($request->getPost('redirect') && strpos($request->getPost('redirect'), $request->getBaseUrl()) !== false) {
            $this->redirect = $request->getPost('redirect');
        } elseif (\Core\Session\Base::get('redirect')) {
            $this->redirect = \Core\Session\Base::get('redirect');
            if ($request->isPost()) {
                \Core\Session\Base::clear('redirect');
            }
        } else {
            $this->redirect = '';
        }
        
        if ($invited && !$request->isPost()) {
            $request->setPost('email', $invited->email);
//             $request->setPost('first_name', $invited->firstname);
//             $request->setPost('last_name', $invited->lastname);
        }

        if ($request->isXmlHttpRequest() && $request->getQuery('callback')) {
            $this->responseJsonCallback(array(
                'content' => $this->render('index', null, null, true),
                'title' => $this->_('Register')
            ));
        } else {
            $this->render('index');
        }
    }

    private function validateRegister() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            if ($request->getPost('X-form-cmd') == $this->x_form_cmd) {
                $validator = new \Core\Form\Validator(array(
                    'translate' => $this->_
                ));
                $validator->addEmail('email');
                $validator->addPassword('password', array(
                    'min' => 3,
                    'error_text_min' => $this->_('Password must contain more than %d characters')
                ));
                $validator->addUsername('username');

                $forms = \Base\FormExtend::getExtension('userForm.register');
                foreach ($forms AS $form) {
                    if ($form->validator) {
                        $validatorName = $form->validator;
                        new $validatorName(array('validator' => $validator, 'parent' => $this, 'type' => 'register'));
                    }
                }
                
                if($request->getPost('password') != $request->getPost('repass')) {
                    $this->errors['repass'] = $this->_('Passwords did not match.');
                }
                
                if ($validator->validate()) {
                    $userTable = new \User\User();
                    // check username if exist
                    if ($userTable->countByUsername($request->getPost('username'))) {
                        $this->errors['username'] = $this->_('This username is already being used');
                    } else if(in_array(strtolower($request->getPost('username')), (new \User\User())->getReserved())) {
						$this->errors['username'] = $this->_('This username is already being used');
					}
                    // check username if exist
                    if ($userTable->countByEmail($request->getPost('email'))) {
                        $this->errors['email'] = $this->_('This e-mail address is already being used');
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
