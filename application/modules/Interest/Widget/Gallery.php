<?php

namespace Interest\Widget;

class Gallery extends \Base\Widget\AbstractMenuPermissionWidget {
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function result() {
		$request = $this->getRequest();
		$data['interests'] = array();
		if($request->getModule() == 'category') {
			$interestTable = new \Interest\Interest();
			
			$interests = $interestTable->fetchAll($interestTable->makeWhere(array(
				'category_id' => $request->getParam('category_id'),
				'show' => 1
			)), null,200);
			
			foreach($interests AS $interest) {
				$data['interests'][] = $interest;
			}
			
			$this->render('index', $data);
		} elseif($request->getModule() == 'interest') {
			$query = $request->getParam('query');
			$interestTable = new \Interest\Interest();
			$data['interest'] = $interest = $interestTable->getByQuery($query);
			
			if($interest) {
				$data['interests'] = $interestTable->getRelated($interest->id);
			}
			
			$this->render('interest', $data);
		}
	}
	
}