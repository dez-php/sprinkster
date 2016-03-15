<?php

namespace Pin;

class AdminController extends \Core\Base\Action {
	
	public function init() {
		$this->_ = new \Translate\Locale('Backend\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() {			
		$this->render('index');
	}
	
	public function totalAction() {		
		$pinTable = new \Pin\Pin();
		$total = $pinTable->countByStatus(1);
		$pinTable->getAdapter()->query("DELETE FROM `statistics` WHERE `type` = 1");
		$pinTable->getAdapter()->query("INSERT INTO `statistics`(`id`, `total`, `type`) SELECT DATE_FORMAT(`date_added`, '%Y%m'),COUNT(id),1 FROM pin WHERE status = 1 GROUP BY DATE_FORMAT(`date_added`, '%Y%m');");
		$this->responseJsonCallback(array('total' => $total));
	}
	
}