<?php

namespace Wishlist;

class CreateController extends \Base\PermissionController {

    private $errors = array();

    public function init() {
        $request = $this->getRequest();
        if ($request->isXmlHttpRequest()) {
            $this->noLayout(true);
        }
        $this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
    }

    public function indexAction() {
        $request = $this->getRequest();
        $data = array(
            'location' => false
        );
        $userInfo = \User\User::getUserData();
        if (!$userInfo->id) {
            $data['location'] = $this->url(array('controller' => 'login'), 'user_c');
        } else {

            $this->email_me = $userInfo->notification_group_wishlist;
            $this->callback_action = $request->getQuery('callback_action');

            $wishlistTable = new \Wishlist\Wishlist();
            $this->x_form_cmd = $wishlistTable->getXFormCmd();

            $data['isXmlHttpRequest'] = $request->isXmlHttpRequest();

            if ($request->isPost() && $this->validate()) {
                $wishlistTable->getAdapter()->beginTransaction();
                try {
                    $new = $wishlistTable->fetchNew();
                    $new->title = $this->escape($request->getPost('title'));
                    //$new->category_id = $this->escape($request->getPost('category_id'));
                    $new->user_id = $userInfo->id;
                    $new->description = $this->escape($request->getPost('description'));
                    $new->email_me = 1; //$this->escape($request->getPost('email_me'));
                    if (isset($new->secret)) {
                        $new->secret = (int) $this->escape($request->getPost('secret'));
                    }
                    if ($new->save()) {
// 						$userInfo->wishlists = $wishlistTable->countByUserId_Status($userInfo->id, 1);
// 						$userInfo->save();
                        $invite = $request->getPost('invite');
                        if ($invite) {
                            $userTable = new \User\User();
                            $users = $userTable->fetchAll($userTable->makeWhere(array('id' => $invite)));
                            ///////share
                            $inviteTable = new \Wishlist\WishlistShare();
                            ///////notify
                            $NotificationTable = new \Notification\Notification();
                            ////////
                            foreach ($users AS $user) {
                                $newShare = $inviteTable->fetchNew();
                                $newShare->wishlist_id = $new->id;
                                $newShare->user_id = $userInfo->id;
                                $newShare->share_id = $user->id;
                                $newShare->date_added = $new->date_added;
                                if ($newShare->save()) {
                                    //send notifikation
                                    $NotificationTable = new \Notification\Notification();
                                    $NotificationTable->send('wishlist_invite', [
                                    	'user_id' => $user->id,
                                        'user_username' => $user->username,
                                        'user_firstname' => $user->firstname,
                                        'user_lastname' => $user->lastname,
                                        'user_username' => $user->username,
                                        'user_fullname' => $user->getUserFullname(),
                                        'wishlist_url' => $this->url(array('wishlist_id' => $new->id, 'query' => $this->urlQuery($new->title)), 'wishlist'),
                                        'wishlist_name' => $new->title,
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
                                \Activity\Activity::set($user->id, 'INVITEWISHLIST', null, $new->id);
                            }
                        }

                        //extend form
                        $forms = \Base\FormExtend::getExtension('wishlistForm.create');
                        foreach ($forms AS $form) {
                            if ($form->save) {
                                $saveName = $form->save;
                                new $saveName(array('wishlist_id' => $new->id, 'parent' => $this, 'type' => 'create'));
                            }
                        }
                        //end extend form

                        $wishlistTable->getAdapter()->commit();
                        $url = $this->url(array('wishlist_id' => $new->id, 'query' => $this->urlQuery($new->title)), 'wishlist');
                        if ($data['isXmlHttpRequest']) {
                            $this->responseJsonCallback(array(
                            	'location' => $url,
                            	'id' => $new->id,
                            	'name' => $new->title
                            ));
                            exit;
                        } else {
                            $this->redirect($url);
                        }
                    } else {
                        $this->errors['newRecord'] = $this->_('There was a problem with the record. Please try again!');
                    }
                } catch (\Core\Exception $e) {
                    $wishlistTable->getAdapter()->rollBack();
                    $this->errors['Exception'] = $e->getMessage();
                }
            }
            if ($data['isXmlHttpRequest'] && $this->errors) {
                $this->responseJsonCallback(array('errors' => $this->errors, 'location' => $data['location']));
                exit;
            }

            $data['categories'] = $this->getCategory();
        }

        $this->render('index', $data);
    }

    public function simpleAction() {
        $this->noLayout(true);
        $request = $this->getRequest();
        $data = array();
        $userInfo = \User\User::getUserData();
        if (!$userInfo->id) {
            $data['location'] = $this->url(array('controller' => 'login'), 'user_c');
        } else {

            $wishlistTable = new \Wishlist\Wishlist();

            if ($request->isPost() && $this->validate(true)) {
                $wishlistTable->getAdapter()->beginTransaction();
                try {
                    $new = $wishlistTable->fetchNew();
                    $new->title = $this->escape($request->getPost('title'));
                    //$new->category_id = $this->escape($request->getPost('category_id'));
                    $new->user_id = $userInfo->id;
// 					$new->description = '';
// 					$new->email_me = 0;
                    if ($new->save()) {
                        //$userInfo->wishlists = $wishlistTable->countByUserId_Status($userInfo->id, 1);
                        //$userInfo->save();

                        $wishlistTable->getAdapter()->commit();
                        $data['ok'] = array('id' => $new->id, 'text' => $new->title);
                    } else {
                        $data['errors']['newRecord'] = $this->_('There was a problem with the record. Please try again!');
                    }
                } catch (\Core\Exception $e) {
                    $wishlistTable->getAdapter()->rollBack();
                    $data['errors']['Exception'] = $e->getMessage();
                }
            }
            if ($this->errors) {
                foreach ($this->errors AS $k => $v) {
                    $data['errors'][$k] = $v;
                }
            }

            $this->responseJsonCallback($data);
        }
    }

    private function validate($simple = false) {
       
        $request = $this->getRequest();
        if ($request->isPost()) {
            if ($request->getPost('X-form-cmd') == $this->x_form_cmd) {
                
                $validator = new \Core\Form\Validator(array(
                    'translate' => $this->_
                ));
//                $validator->addNumber('category_id', array(
//                    'min' => 1,
//                    'error_text' => $this->_('You have to choose category')
//                ));
                $validator->addText('title', array(
                    'min' => 3,
                    'error_text_min' => $this->_('Title must contain more than %d characters')
                ));
                
                if (!$simple) {
                    $forms = \Base\FormExtend::getExtension('wishlistForm.create');
                    foreach ($forms AS $form) {
                        if ($form->validator) {
                            $validatorName = $form->validator;
                            new $validatorName(array('validator' => $validator, 'parent' => $this, 'type' => 'create'));
                        }
                    }
                }
                
                if ($validator->validate()) {
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

    private function getCategory($parent_id = null, $level = -1) {
        $categoryTable = new \Category\Category();
        $categories = $categoryTable->getAllIdTitle($parent_id);
        $return = array();
        if ($categories) {
            $level++;
            foreach ($categories AS $category) {
                $childs = $this->getCategory($category->id, $level);
                /* if($childs) {
                  $category->disabled = true;
                  } else {
                  $category->disabled = false;
                  } */
                $category->disabled = false;
                $category->title = $categoryTable->getPathFromChild($category->id, ' -> '); //str_pad('', $level, "-", STR_PAD_LEFT) . $category->title;
                $return[$category->id] = $category;
                if ($childs) {
                    $return = \Core\Arrays::array_merge($return, $childs);
                }
            }
        }
        return $return;
    }

}
