<?php
namespace Paypal\Helper;

class Settings {

	public function __construct($options = [])
	{
		$me = \User\User::getUserData();
		$request = \Core\Http\Request::getInstance();

		if(!$me->id)
			return;

		$data = $request->getPost('paypal');

		if(!is_array($data) || empty($data))
			return;

		$settings = \Paypal\Settings::get($me->id);

		$settings->active = isset($data['active']);

		if(!isset($data['active']))
			return (new \Paypal\Settings)->delete([ 'user_id = ?' => $me->id ]);

		if(!isset($data['active']))
			throw new \Core\Exception('No email given.');

		$settings->email = $data['email'];

		if(!$settings->save())
			throw new \Core\Exception('Could not save PayPal settings.');
	}

}