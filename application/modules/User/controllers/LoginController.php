<?php
namespace User;


class LoginController extends \Core\Base\Action {
	
	/**
	 * @var null|array
	 */
	public $errors;
	
	public function init() {
		$request = $this->getRequest();
		if($request->getQuery('next') && strpos($request->getQuery('next'), $request->getBaseUrl()) !== false) {
			\Core\Session\Base::set('redirect', $request->getQuery('next'));
			$url = $this->url(array('controller' => 'login'),'user_c');
			if($request->isXmlHttpRequest()) {
				$url .= strpos($url, '?') !== false ? '&' : '?';
				if($request->getParam('RSP')) { $url .= '&RSP=' . $request->getParam('RSP'); }
				elseif($request->isXmlHttpRequest()) { $url .= '&RSP=ajax&popup=true'; } 
				if($request->getParam('popup')) { $url .= '&popup=' . $request->getParam('popup'); } 
				if($request->getParam('callback')) { $url .= '&callback=' . $request->getParam('callback'); }
				if($request->getParam('_')) { $url .= '&_=' . $request->getParam('_'); } 
				
			}
			$this->redirect( $url );
		}
        if($request->isXmlHttpRequest()) {
            $this->noLayout(true);
        }
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() {
		$request = $this->getRequest();
		
		//if loget redirect
		if(\User\User::getUserData()->id) {
			if($request->isXmlHttpRequest())
				return $this->responseJsonCallback([ 'location' => $this->url(array(),'welcome_home') ]);

			$this->redirect( $this->url(array(),'welcome_home') );
		}
		
		$userTable = new \User\User();
		
		$this->x_form_cmd = $userTable->getXFormCmd();
		
		//check login and redirect if true
		if( $this->validateLogin() && ($user_id = $userTable->validateLogin($request->getPost('email'), $request->getPost('password'))) !== false ) {
			\Permission\Permission::flush();
			
			// Added strpos check to pass McAfee PCI compliance test
			$redirect = $request->getPost('redirect');
			\Core\Session\Base::set('login_discourse', 'http://boards.sprinkster.com/session/sso');
			if($redirect && strpos($redirect, $request->getBaseUrl()) !== false) {
				
				if($request->isXmlHttpRequest())
					return $this->responseJsonCallback([ 'location' => str_replace('&amp;', '&', $redirect) ]);
				$this->redirect( str_replace('&amp;', '&', $redirect) );
			} else {
				
				if($request->isXmlHttpRequest())
					return $this->responseJsonCallback([ 'location' => $this->url(array(),'welcome_home') ]);

				$this->redirect( $this->url(array(),'welcome_home') );
			}
		}
		
		// set error's
		if(!$this->errors && ($error = $userTable->getErrors()) !== null ) {
			if($error == \User\User::USER_NOT_FOUND) {
				$this->errors['email'] = $this->_('User not found');
			} else if($error == \User\User::WRONG_PASSWORD) {
				$this->errors['password'] = $this->_('Incorrect password entered');
			} else if($error == \User\User::USER_NOT_ACTIVE) {
				$this->errors['warning'] = $this->_('The email address for this account has not yet been verified. Please check your email for the activation link');
			}
		}
		
		// Added strpos check to pass McAfee PCI compliance test
		if($request->getPost('redirect') && strpos($request->getPost('redirect'), $request->getBaseUrl()) !== false) {
			$this->redirect = $request->getPost('redirect');
		} elseif (\Core\Session\Base::get('redirect')) {
			$this->redirect = \Core\Session\Base::get('redirect');
			if($request->isPost()) {
				\Core\Session\Base::clear('redirect');
			}
		} else {
			$this->redirect = '';
		}
		
		if($request->isXmlHttpRequest() && $request->getQuery('callback')) {
            $this->responseJsonCallback(array(
                'content' => $this->render('index',null,null,true),
                'title' => $this->_('Login')
            ));
        } else {
            $this->render('index');
        }
	}
	
	/**
	 * @deprecated
	 * @param  array $params Params for login
	 * @return mixed         HTML Output or JSON data
	 */
	public function popupAction($params) {
		
		$request = $this->getRequest();
		
		if(!isset($params['url']) && !$request->isPost())
			$this->forward('error404');
		if($request->isPost() && !$request->getPost('link'))
			$this->forward('error404');
		
		if(!isset($params['action']) && !$request->isPost())
			$this->forward('error404');
		if($request->isPost() && !$request->getPost('js_action'))
			$this->forward('error404');
		
		//if loget redirect
		if(\User\User::getUserData()->id) {
			$this->redirect( $this->url(array(),'welcome_home') );
		}
		
		$userTable = new \User\User();
		
		$this->x_form_cmd = $userTable->getXFormCmd();
		
		$data = [
			'js_action' => isset($params['action']) ? $params['action'] : null,
			'link' => isset($params['url']) ? $params['url'] : null
		];
		
		if($request->isPost()) {
			$data['link'] = $request->getPost('link');
			$data['js_action'] = $request->getPost('js_action');
			if(!$this->validateLogin())
				return $this->responseJsonCallback(['errors' => $this->errors]);
			if($userTable->validateLogin($request->getPost('email'), $request->getPost('password')) === false && ($error = $userTable->getErrors()) !== null) {
				if($error == \User\User::USER_NOT_FOUND) {
					$this->errors['email'] = $this->_('User not found');
				} else if($error == \User\User::WRONG_PASSWORD) {
					$this->errors['password'] = $this->_('Incorrect password entered');
				} else if($error == \User\User::USER_NOT_ACTIVE) {
					$this->errors['warning'] = $this->_('User not activated');
				} else {
					$this->errors['null'] = $this->_('Undefined Error!');
				}
				return $this->responseJsonCallback(['errors' => $this->errors]);
			}
			
			\Permission\Permission::flush();

			return $this->responseJsonCallback([
				'success' => true,
				'link' => $data['link']
			]);
			
		}

		$this->render('popup', $data);
		
	}
	
	public function logoutAction() {
		\Permission\Permission::flush();
		
		$userTable = new \User\User();
		$userTable->logout();
		\Core\Session\Base::clear('redirect');
		$this->redirect( $this->url(array(),'welcome_home') );
	}

	private function validateLogin() {
		$request = $this->getRequest();
		if($request->isPost()) {
			if( $request->getPost('X-form-cmd') == $this->x_form_cmd ) {
				$validator = new \Core\Form\Validator(array(
					'translate' => $this->_		
				));
				$validator->addEmail('email');
				$validator->addPassword('password');
				if($validator->validate()) {
					return true;
				} else {
					$this->errors = $validator->getErrors();
				}
			} else {
				$this->errors['x-form-cmd'] = $this->_('Incorrect form data');
			}
		}
		return false;
	}
	
}