<?php

namespace Base\Widget;

use \Core\Interfaces\IPersistentWidget;

use \Base\Traits\AcceptedResponseDetection;

abstract class UserTabWidget extends \Core\Base\Widget implements IPersistentWidget {

	protected $tab_id = NULL;
	protected $title = NULL;
	protected $user = NULL;
	protected $data = [];

	public abstract function tab();
	public abstract function content();

	public function __construct()
	{
		$this->user = \User\User::getUserData();
		$this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function result()
	{
		return TRUE;
	}

	public function ID()
	{
		return $this->tab_id ? mb_strtolower($this->tab_id) : NULL;
	}

}