<?php

namespace Interest;

class AdminController extends \Core\Base\Action {
	
	public function init() {
		if(!\User\User::getUserData()->is_admin) { $this->redirect($this->url([],'admin_login')); }
		$this->_ = new \Translate\Locale('Backend\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() {		
		$this->render('index');
	}
	
	public function editAction() {		
		$this->render('edit');
	}
	
	public function createAction() {		
		$this->render('edit');
	}
	
	public function autocompleteAction() {
		$searchIndex = new \Search\SearchIndex();
		$results = array();
		if($this->getRequest()->getQuery('q')) {
			$rows = $searchIndex->fetchAll($searchIndex->makeWhere(array(
				'word' => new \Core\Db\Expr($searchIndex->getAdapter()->quote($this->getRequest()->getQuery('q') . '%'))
			)), 'word ASC', 500);
			foreach($rows AS $row) {
				$results[] = array(
					'id' => $row->id,
					'label' => $row->word,
					'value' => $row->word
				);
			}
		}
		$this->responseJsonCallback($results);
	}
	
}