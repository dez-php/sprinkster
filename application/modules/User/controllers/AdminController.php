<?php

namespace User;

class AdminController extends \Core\Base\Action {
	
	public function init() {
		$this->_ = new \Translate\Locale('Backend\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() {		
		$this->render('index');
	}
	
	public function editAction() {		
		$this->render('edit');
	}
	
	public function totalAction() {		
		$userTable = new \User\User();
		$total = $userTable->countByStatus(1);
		$userTable->getAdapter()->query("DELETE FROM `statistics` WHERE `type` = 2");
		$userTable->getAdapter()->query("INSERT INTO `statistics`(`id`, `total`, `type`) SELECT DATE_FORMAT(`date_added`, '%Y%m'),COUNT(id),2 FROM user WHERE status = 1 GROUP BY DATE_FORMAT(`date_added`, '%Y%m');");
		$this->responseJsonCallback(array('total' => $total));
	}
	
}