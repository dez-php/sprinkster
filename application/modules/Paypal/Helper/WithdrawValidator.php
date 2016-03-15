<?php
namespace Paypal\Helper;

class WithdrawValidator {

	public function __construct($options = [])
	{
		if(!isset($options['validator']) || !($options['validator'] instanceof \Core\Form\Validator))
			return $this;

		$validator = $options['validator'];
		$translate = new \Translate\Locale('Front\\' . __NAMESPACE__, \Core\Base\Action::getModule('Language')->getLanguageId());
		$request = \Core\Http\Request::getInstance();
		$data = $request->getPost('withdraw[data]');

		$validator->addEmail('withdraw[data][email]', [
			'min' => 5,
			'custom-value' => $data['email'],
			'error_text' => $translate->_('Please supply valid seller e-mail address for PayPal.'),
		]);
	}

}