<?php

namespace Wishlist;

class IndexController extends \Base\PermissionController {
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() {	
		$request = $this->getRequest();

		//get wishlist_id
		$this->wishlist_id = $request->getRequest('wishlist_id');
		
		$wishlistTable = new \Wishlist\Wishlist();
		
		$data['wishlist'] = $wishlistTable->get($this->wishlist_id);
		
		if(!$data['wishlist']) {
			$this->forward('error404');
		}
		
		$userTable = new \User\User();
		$data['user'] = $userTable->fetchRow(array('id = ?' => $data['wishlist']->user_id));
		
		$wishlistShareTable = new \Wishlist\WishlistShare();
		$data['users'] =  $userTable->fetchAll($userTable->makeWhere(array('id' => array($wishlistShareTable->select()->from($wishlistShareTable,'share_id')->where('wishlist_id = ?', $data['wishlist']->id)->where('accept = 1')))));
		
	//	var_dump($data['users']);

		$pinRepinTable = new \Pin\PinRepin();
		$data['wishlist_pins'] = $pinRepinTable->countBy(['wishlist_id' => $this->wishlist_id]);
		
		if($data['wishlist']->module && \Install\Modules::isInstalled($data['wishlist']->module)) {
			$this->forward($data['wishlist']->action, $data, $data['wishlist']->controller, $data['wishlist']->module);
		}
		
		//set page metatags
		$this->placeholder('meta_tags', $this->render('header_metas', $data,null,true));
		
		//render script
		$this->render('index', $data);
	}
	
}

?>