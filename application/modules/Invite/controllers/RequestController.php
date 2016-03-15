<?php

namespace Invite;

use \Base\Traits\Errorly;

class RequestController extends \Base\PermissionController {
	
	use Errorly;

	protected $errors = [];

	public function init()
	{
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());

		if($this->getRequest()->isXmlHttpRequest())
			$this->noLayout(TRUE);
	}
	
	public function indexAction()
	{
		$request = $this->getRequest();
		$isXmlHttpRequest = $request->isXmlHttpRequest();

		if(\User\User::getUserData()->id)
			return $isXmlHttpRequest ? $this->responseJsonCallback([ 'location' => $this->url([],'welcome_home') ]) : $this->redirect($this->url([],'welcome_home'));
		
		if(\Core\Session\Base::get('success'))
		{
			$this->success = $this->_('A request for invitation was sent to the site owner. You will be notified by email for your request.');
			\Core\Session\Base::clear('success');
		}
		
		$this->x_form_cmd = (new \User\User)->getXFormCmd();
		
		if($this->validateRequest())
		{
			$new = (new \Invite\Invite)->fetchNew();
			$new->code = md5($this->x_form_cmd . microtime(TRUE) . $request->getPost('email'));
			$new->email = $request->getPost('email');
			
			$success = $new->save();

			if($success && !$isXmlHttpRequest)
			{
				\Core\Session\Base::set('success',TRUE);
				$this->redirect($this->url([ 'controller' => 'request' ],'invite_c'));
			}

			if($isXmlHttpRequest)
				return $this->responseJsonCallback([ 'errors' => $this->errors, 'success' => $success ? $this->_('A request for invitation was sent to the site owner. You will be notified by email for your request.') : FALSE ]);

			$this->errors['errorNew'] = $this->_('There was a problem with the record. Please try again!');
		}

		if($request->isPost() && $isXmlHttpRequest)
				return $this->responseJsonCallback([ 'errors' => $this->errors, 'success' => FALSE ]);

		$this->render('index', [ 'isXmlHttpRequest' => $isXmlHttpRequest ]);
	}

	private function validateRequest()
	{
		$request = $this->getRequest();
		$email = $request->getPost('email');

		if(!$request->isPost())
			return FALSE;

		if($request->getPost('X-form-cmd') !== $this->x_form_cmd)
		{
			$this->errors['x-form-cmd'] = $this->_('Incorrect form data');
			return FALSE;
		}

		$validator = new \Core\Form\Validator([ 'translate' => $this->_ ]);
		$validator->addEmail('email');

		if(!$validator->validate())
		{
			$this->errors = $validator->getErrors();
			return FALSE;
		}

		if((new \User\User)->countByEmail($email) || (new \Invite\Invite)->countByEmail($email))
		{
			$this->errors['email'] = $this->_('This e-mail address is already used');
			return FALSE;
		}

		return !$this->errors;
	}
	
}