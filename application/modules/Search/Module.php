<?php

namespace Search;

class Module extends \Core\Base\Module
{
	
	public function registerEvent( \Core\Base\Event $e, $application ) {
		if(!$this->getRequest()->isXmlHttpRequest())
			return;
		$self = $this;
		$e->register('onBeforeDispatch', function() use($self) {
			$request = $self->getRequest();
			if($request->getParam('___layout___') == 'admin') 
				return;
			(new \Pin\PinSearchProvider)->register();
			(new \User\UserSearchProvider)->register();
			(new \Wishlist\WishlistSearchProvider)->register();
		});
	}
	
}
