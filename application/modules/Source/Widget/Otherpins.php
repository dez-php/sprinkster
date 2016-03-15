<?php

namespace Source\Widget;

class Otherpins extends \Base\Widget\PermissionWidget {

	protected $pin;
	protected $limit;
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function setLimit($limit) {
		$this->limit = $limit;
		return $this;
	}
	
	public function setPin($pin) {
		$this->pin = $pin;
		return $this;
	}
	
	public function getPage() {
		$request = $this->getRequest();
		$limit = (int)$request->getQuery('limit');
		
		if($limit < 1) { 
			$limit = 1; 
		}
		
		$page = (int)$request->getQuery('page');
		$pin_id = (int)$request->getQuery('pin_id');
		
		if($page < 1) { 
			$page = 1; 
		} 
		$data['popup'] = $request->getQuery('popup');
		$offset = ($page*$limit) - $limit;
		
		$source_id = $request->getQuery('source_id');
		$sourceTable = new \Source\Source();
		$source_info = $sourceTable->fetchRow(array('id = ?' => $source_id));
		if($source_info && $source_info['pins'] > ($page-1)*$limit) {
			$data['source'] = $source_info;
			$pinsTable = new \Pin\Pin();
			$data['pins'] = $pinsTable->getAll($pinsTable->makeWhere(array('source_id' => $source_id, 'status' => 1, 'id' => '!='.$pin_id)), 'id DESC', $limit,$offset);
			$this->responseJsonCallback($this->render('page', $data,true));
		} else {
			$this->responseJsonCallback(false);
		}
	}
	
	
	public function result() {
		$request = $this->getRequest();
		
		if($request->getQuery('source_id') && $request->getQuery('page')) {
			return $this->getPage();
		}
		
		if(!$this->pin || !$this->pin->source_id)
			return;

		$data = array(
			'pins' => false,
			'init' => false,
		);

		$data['popup'] = $request->getQuery('popup');
		
// 		if($request->getRequest('getuserinfo') == 'true') {
			$sourceTable = new \Source\Source();
			$source_info = $sourceTable->fetchRow(array('id = ?' => $this->pin->source_id));
			
			if($source_info && $source_info->pins > 1) {
				// filter pins by category_id
				
				if((int)$this->limit) {
					$limit = min((int)$this->limit,$source_info->pins);
				} else {
					$limit = min(9,$source_info->pins);
				}
				
				if($limit < 1) { return; }
				
				$data['source'] = $source_info;
				
				$pinsTable = new \Pin\Pin();

				$filter = $pinsTable->makeWhere([
					'source_id' => $this->pin->source_id,
					'id' => '!='.$this->pin->id
				]);
				$data['total'] = $pinsTable->getCount($filter);
				$data['pins'] = $pinsTable->getAll($filter,'id DESC', $limit);
			}
// 		} else {
// 			$data['init'] = true;
// 			$data['pin_id'] = $this->pin->id;
// 			$data['source_id'] = $this->pin->source_id;
// 		}
		
		$this->render('otherpins', $data);
		
	}
	
	
}