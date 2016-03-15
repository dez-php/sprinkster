<?php

namespace Wishlist\Widget;

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
		if($limit < 1) { $limit = 1; }
		$page = (int)$request->getQuery('page');
		if($page < 1) { $page = 1; } 
		$data['popup'] = $request->getQuery('popup');
		$offset = ($page*$limit) - $limit;
		$wishlist_id = $request->getQuery('wishlist_id');
		$wishlistTable = new \Wishlist\Wishlist();
		$wishlist_info = $wishlistTable->get($wishlist_id);
		if($wishlist_info && $wishlist_info['pins'] > $page*$limit) {
			$data['wishlist'] = $wishlist_info;
			$pinsTable = new \Pin\Pin();
			$data['pins'] = $pinsTable->fetchAll($pinsTable->makeWhere(array('wishlist_id' => $wishlist_id, 'status' => 1)), 'id DESC', $limit,$offset);
			$this->responseJsonCallback($this->render('page', $data,true));
		} else {
			$this->responseJsonCallback(false);
		}
	}
	
	public function result() {
		$request = $this->getRequest();
		
		if($request->getQuery('wishlist_id') && $request->getQuery('page')) {
			return $this->getPage();
		}
		
		if(!$this->pin)
			return;
		
		$data = array(
			'pins' => false
		);
		$data['popup'] = $request->getQuery('popup');
		
		if($this->pin && $this->pin->wishlist_id) {
			$wishlistTable = new \Wishlist\Wishlist();
			$wishlist_info = $wishlistTable->get($this->pin->wishlist_id);

			if($wishlist_info && $wishlist_info['pins'] > 1) {
				// filter pins by category_id
				if((int)$this->limit) {
					$limit = min((int)$this->limit,$wishlist_info['pins']);
				} else {
					$limit = min(6,$wishlist_info['pins']);
				}

				if($limit < 1) { return; }

				$this->limit = $limit;
				$this->limit2 = min(6,(int)$this->limit?(int)$this->limit:6);
				$data['wishlist'] = $wishlist_info;

				$pinsTable = new \Pin\Pin();
				$data['pins'] = $pinsTable->fetchAll($pinsTable->makeWhere(array('wishlist_id' => $this->pin->wishlist_id, 'status' => 1, 'id' => '!='.$this->pin->id)), 'id DESC', $limit);
			}
		}
		$this->render('otherpins', $data);
		
	}
	
	
}