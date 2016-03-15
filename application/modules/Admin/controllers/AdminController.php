<?php

namespace Admin;

class AdminController extends \Core\Base\Action {
	
	public function init() {
		if(!\User\User::getUserData()->is_admin) { $this->redirect($this->url([],'admin_login')); }
		$this->_ = new \Translate\Locale('Backend\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() {			
		$data = array(
			'has_update' => $this->getAdmin()->versionChecker()		
		);
		
		$this->render('index', $data);
	}
	
	public function menuAction() {
		
		if($this->getRequest()->isPost()) {
			$ids = $this->getRequest()->getPost('id');
			$sort = array();
			$menuTable = new \Base\Menu();
			$menuTable->getAdapter()->beginTransaction();
			try {
				foreach($ids AS $id => $parent_id) {
					if(!isset($sort[$parent_id])) { $sort[$parent_id] = 1; }
					$sort_order = $sort[$parent_id]++; 
					$parent_id = $parent_id == 'null' ? null : $parent_id;
					$menuTable->update(array(
						'parent_id' => $parent_id,
						'sort_order' => $sort_order
					), array('id = ?' => $id));
				}
				$menuTable->getAdapter()->commit();
				echo 'ok';
			} catch (\Core\Db\Exception $e) {
				$menuTable->getAdapter()->rollBack();
				echo $e->getMessage();
			}
			
			exit;
		}
		
		$this->render('menu');
	}
	
}