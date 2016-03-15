<?php

namespace Wishlist\Widget;

class Accept extends \Core\Base\Widget {

	protected $user = NULL;
	protected $wishlist = NULL;

	public function init()
	{
        $this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
        $this->user = \User\User::getUserData();
        $this->wishlist = isset($this->options['wishlist']) ? $this->options['wishlist'] : NULL;
	}

	public function result()
	{
		if(!$this->user->id || !$this->wishlist || !$this->wishlist->id)
			return;

		$share = (new \Wishlist\WishlistShare)->fetchRow([ 'accept IS NULL', 'share_id = ?' => $this->user->id, 'wishlist_id = ?' => $this->wishlist->id ]);

		if(!$share)
			return;

		$this->render('index', [ 'share' => $share, 'wishlist' => $this->wishlist ]);
	}

}