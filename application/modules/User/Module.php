<?php
namespace User;

class Module extends \Core\Base\Module
{
	public function registerEvent( \Core\Base\Event $e, $application ) {
		if($this->getRequest()->isXmlHttpRequest())
			return;
		$e->register('onBeforeDispatch.pin.user.wishlist', [ $this, 'onBeforeDispatch' ]);
	}

	public function onBeforeDispatch()
	{
		$request = $this->getRequest();

		if($request->getParam('___layout___') == 'admin')
			return;

		$user = null;
		if( !is_null($user_id = $request->getParam('user_id')) ) {
			$userTable = new \User\User();
			$user = $userTable->fetchRow(array('id = ?' => $user_id));
		} elseif( !is_null($wishlist_id = $request->getParam('wishlist_id')) ) {
			$wishlistTable = new \Wishlist\Wishlist();
			$userTable = new \User\User();
			$user = $userTable->fetchRow($userTable->makeWhere(array('id' => array($wishlistTable->select()->from($wishlistTable,'user_id')->where('id = ?', $wishlist_id)))));
		} elseif( !is_null($pin_id = $request->getParam('pin_id')) ) {
			$pinTable = new \Pin\Pin();
			$userTable = new \User\User();
			$user = $userTable->fetchRow($userTable->makeWhere(array('id' => array($pinTable->select()->from($pinTable,'user_id')->where('id = ?', $pin_id)))));
		}
		if($user && $user->search_engines) {
			$document = $this->getComponent('document');
			$document->addMetaTag('noindex, nofollow', 'robots');
		}
		if($user && !is_null($user_id = $request->getParam('user_id'))) {
			$action = \Core\Base\Action::getInstance();
			//set page metatags
			$action->placeholder('meta_tags', $action->render('header_metas', array('user'=>$user),['module'=>'user','controller'=>'index'],true));		
		}
	}

	
}
