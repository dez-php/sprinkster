<?php

namespace Permission;

class AdminController extends \Core\Base\Action {
	
	public function init()
	{
		$this->_ = new \Translate\Locale('Backend\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction()
	{
		$this->render('index');
	}
	
	public function editAction()
	{
		$group_id = (int) $this->getRequest()->getRequest('id');

		$this->render('edit', [
			'all' => Permission::getAll(),
			'available' => Permission::getAvailable($group_id),
			'enabled' => Permission::getEnabled($group_id),
		]);
	}
	
	public function createAction()
	{
		throw new \Exception('There is no create option for permissions.');
	}

	public function groupAction()
	{
		$this->render('groups');
	}

	public function creategroupAction()
	{
		$this->render('groupedit', []);
	}

	public function editgroupAction()
	{
		$this->render('groupedit', []);
	}
	
}