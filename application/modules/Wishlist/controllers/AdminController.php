<?php

namespace Wishlist;

class AdminController extends \Core\Base\Action {
	
	public function init() {
		$this->_ = new \Translate\Locale('Backend\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() {		
		$this->render('index');
	}
	
	public function totalAction() {		
		$wishlistTable = new \Wishlist\Wishlist();
		$total = $wishlistTable->countByStatus(1);
		$wishlistTable->getAdapter()->query("DELETE FROM `statistics` WHERE `type` = 3");
		$wishlistTable->getAdapter()->query("INSERT INTO `statistics`(`id`, `total`, `type`) SELECT DATE_FORMAT(`date_added`, '%Y%m'),COUNT(id),3 FROM wishlist WHERE status = 1 GROUP BY DATE_FORMAT(`date_added`, '%Y%m');");
		$this->responseJsonCallback(array('total' => $total));
	}
	
}