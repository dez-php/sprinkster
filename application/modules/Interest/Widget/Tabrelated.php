<?php

namespace Interest\Widget;

class Tabrelated extends \Base\Widget\AbstractMenuPermissionWidget {

	protected $record_id;
	protected $form;
	protected $widget;
	protected $type;
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function result() {
		$method_name = 'get' . $this->type;
		if(method_exists($this, $method_name)) {
			return $this->$method_name();
		}
	}
	
	public function autocompleteAction() {
		$searchIndex = new \Interest\Interest();
		$results = array();
		if($this->getRequest()->getQuery('q')) {
			$filter = array(
				'title' => new \Core\Db\Expr($searchIndex->getAdapter()->quote($this->getRequest()->getQuery('q') . '%'))
			);
			if($this->getRequest()->getQuery('category_id')) {
				$filter['category_id'] = $this->getRequest()->getQuery('category_id');
			}
			if($this->getRequest()->getQuery('item_id')) {
				$filter['id'] = '!= '.$this->getRequest()->getQuery('item_id');
			}
			$rows = $searchIndex->fetchAll($searchIndex->makeWhere($filter), 'title ASC', 500);
			foreach($rows AS $row) {
				$results[] = array(
					'id' => $row->id,
					'label' => $row->title,
					'value' => $row->title
				);
			}
		}
		$this->responseJsonCallback($results);
	}
	
	protected function getForm() {
		$request = $this->getRequest();
		if ($request->isPost()) {
			$relateds = $request->getPost('related_interest') ? $request->getPost('related_interest') : array();
		} elseif ($this->record_id) {
			$relateds = array();
			$searchIndex = new \Interest\InterestRelated();
			$relatedsData = $searchIndex->fetchAll($searchIndex->makeWhere(array('interest_id' => $this->record_id)));
			foreach($relatedsData AS $r) {
				$relateds[] = $r->related_id;
			}
		} else {
			$relateds = array();
		}
		
		$data['related_interest'] = array();
		$interestIndex = new \Interest\Interest();
		foreach($relateds AS $related) {
			$int = $interestIndex->fetchRow(array('id = ?' => $related));
			if($int) {
				$data['related_interest'][] = array(
					'id' => $int->id,
					'title' => $int->title		
				);
			}
		}
		
		$this->render('index', $data);
	}
	
	protected function getValidate() {
		
	}
	
	protected function getSave() {
		if($this->record_id) {
			$searchIndex = new \Interest\InterestRelated();
			$searchIndex->delete($searchIndex->makeWhere(array('interest_id' => $this->record_id)));
// 			$searchIndex->delete($searchIndex->makeWhere(array('related_id' => $this->record_id)));
			$product_related = $this->getRequest()->getPost('related_interest');
			if ($product_related) {
				foreach ($product_related as $related_id) {
					if($related_id && $related_id != $this->record_id) {
						$searchIndex->delete($searchIndex->makeWhere(array('interest_id' => $this->record_id, 'related_id' => $related_id)));
						$searchIndex->insert(array('interest_id' => $this->record_id, 'related_id' => $related_id));
// 						$searchIndex->delete($searchIndex->makeWhere(array('interest_id' => $related_id, 'related_id' => $this->record_id)));
// 						$searchIndex->insert(array('interest_id' => $related_id, 'related_id' => $this->record_id));
					}
				}
			}
		}
	}
	
}