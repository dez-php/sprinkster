<?php
namespace Paypal\Helper;

class SettingsValidator {

	public function __construct($options = [])
	{
		if(!isset($options['validator']) || !($options['validator'] instanceof \Core\Form\Validator))
			return $this;

		$validator = $options['validator'];
		$translate = new \Translate\Locale('Front\\' . __NAMESPACE__, \Core\Base\Action::getModule('Language')->getLanguageId());
		$request = \Core\Http\Request::getInstance();
		$data = $request->getPost('paypal');

		if(!is_array($data) || empty($data))
			return;

		if(!isset($data['active']))
			return;

		$validator->addEmail('paypal[email]', [
			'min' => 5,
			'custom-value' => $data['email'],
			'error_text' => $translate->_('Please supply valid seller e-mail address for PayPal.'),
		]);
		
		$me = \User\User::getUserData();
		
		if( (new \Paypal\Settings())->countByEmail_UserId($data['email'], '!=' . (int)$me->id) ) {
			$validator->addEmail('paypal[xxxxxxxxxxxxxxxxxxxxxx]', [
				'min' => 1,
				'custom-value' => '',
				'error_text' => $translate->_('E-mail address for PayPal is already used.'),
			]);
		}
	}

}