<?php
namespace Paymentgateway\Widget;

use \Core\Base\Widget;
use \Base\Menu;

use \Base\Traits\XFormCmd;

class Providersettings extends Widget {

	use XFormCmd;

	private $errors = [];
	private $success = FALSE;

	public function init() {
		$this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function result()
	{
		$me = \User\User::getUserData();
		$request = $this->getRequest();
		$forms = \Base\FormExtend::getExtension('providerSettings.settings');

		$this->errors = [];
		$this->success = FALSE;

		if ($this->validate())
		{
			foreach ($forms AS $form)
			{
				if (!$form->save)
					continue;
				
				$saveName = $form->save;

				if(!class_exists($saveName))
					continue;

				new $saveName();
			}

			$this->success = $this->_('Your payment provider settings were saved successfully.');
		}

		$this->render('index', [ 'me' => $me, 'forms' => $forms, 'errors' => $this->errors, 'success' => $this->success ]);
	}

	public function validate()
	{
		$request = $this->getRequest();
		
		if (!$request->isPost())
			return FALSE;

		if(!$request->getPost('x-form-cmd') || $request->getPost('x-form-cmd') !== $this->getXFormCmd())
		{
			$this->errors['x-form-cmd'] = $this->_('Incorrect form data');
			return;
		}

		$validator = new \Core\Form\Validator([ 'translate' => $this->_ ]);

		$forms = \Base\FormExtend::getExtension('providerSettings.settings');
		foreach ($forms AS $form)
		{
			if (!$form->validator)
				continue;

			$validatorName = $form->validator;

			if(!class_exists($validatorName))
				continue;

			new $validatorName([ 'validator' => $validator ]);
		}

		if (!$validator->validate())
			$this->errors = $validator->getErrors();

		return !$this->errors;
	}

}