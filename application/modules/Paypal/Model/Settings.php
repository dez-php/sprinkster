<?php
namespace Paypal;

use \Base\Config;

class Settings extends \Base\Model\Reference {

	protected $_name = "paypal_settings";

	protected $_referenceMap = [
		'User' => [
            'columns' => 'user_id',
            'refTableClass' => 'User\User',
            'refColumns' => 'id'
        ],
	];

	public static function get($user_id)
	{
		if(NULL === $user_id)
		{
			$settings = (new self)->fetchNew();
			$settings->user_id = NULL;
			$settings->active = TRUE;
			$settings->email = Config::get('paypal_business');

			return $settings;
		}

		$settings = (new self)->fetchRow([ 'user_id = ?' => (int) $user_id ]);

		if($settings)
			return $settings;

		$settings = (new self)->fetchNew();
		$settings->user_id = \User\User::getUserData()->id;

		return $settings;
	}

}