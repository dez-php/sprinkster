<?php

namespace Cart;

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
		$userTable = new \Paymentgateway\OrderManager();
		$total = $userTable->countByStatusId('>0');
		$userTable->getAdapter()->query("DELETE FROM `statistics` WHERE `type` = 5");
		$userTable->getAdapter()->query("INSERT INTO `statistics`(`id`, `total`, `type`) SELECT DATE_FORMAT(`created_at`, '%Y%m'),COUNT(id),5 FROM `order` WHERE status_id > 0 GROUP BY DATE_FORMAT(`created_at`, '%Y%m');");
		$this->responseJsonCallback(array('total' => $total));
	}
	
}