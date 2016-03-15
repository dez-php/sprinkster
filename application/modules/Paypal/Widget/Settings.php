<?php
namespace Paypal\Widget;

use \Base\Traits\FormInputPopulator;
use \Base\Traits\Errorly;

class Settings extends \Core\Base\Widget {

	use FormInputPopulator;
	use Errorly;

	protected $me = NULL;

	public function init() {
		$this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function result()
	{
		$this->me = \User\User::getUserData();

		if(!$this->me->id)
			return;

		$settings = \Paypal\Settings::get($this->me->id);

		$this->render('index', [ 'settings' => $settings ]);
	}

}