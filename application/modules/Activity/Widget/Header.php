<?php

namespace Activity\Widget;

class Header extends \Base\Widget\UserTabWidget {

	protected $tab_id = 'activity';
	protected $check = NULL;

	public function __construct()
	{
		parent::__construct();
		$this->check = $this->user->activity_open;
	}
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function tab()
	{
		$data = [];

		if(!$this->user->id)
			return;

		$data['total'] = $this->user->activity_open && FALSE === strpos($this->user->activity_open, '0000') ? (new \Activity\Activity)->countByToUserId_DateAdded($this->user->id, '>=' . \Core\Date::getInstance($this->check, \Core\Date::SQL_FULL, TRUE)->toString()) : 0;

		$this->render('tab', $data);
	}

	public function content()
	{
		$data = [];

		if(!$this->user->id)
			return;

		$where = [ 'to_user_id' => $this->user->id ];
		$max = (int) $this->getRequest()->getParam('max');

		if (0 < $max)
			$where['id'] = '<' . $max;
		
		$activity = new \Activity\Activity;
		$data['activity'] = $activity->getAll($activity->makeWhere($where), 'id DESC', 10);
		$data['check'] = $this->check;

		$this->render('tabcontent', $data);
	}

	public function readAction()
	{
		if(!$this->user || !$this->user->id)
			return;

		(new \User\User())->update([
			'activity_open' => \Core\Date::getInstance(NULL, \Core\Date::SQL_FULL, TRUE)->toString()
		], ['id = ?' => $this->user->id]);
	}

}