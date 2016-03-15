<?php

namespace Pin;

class PinRepinRow extends \Core\Db\Table\Row {

	/**
	 * Allows post-insert logic to be applied to row.
	 * Subclasses may override this method.
	 *
	 * @return void
	 */
	protected function _postInsert()
	{
		(new \Wishlist\Wishlist())->updateInfo($this->wishlist_id);
	}

	/**
	 * Allows post-update logic to be applied to row.
	 * Subclasses may override this method.
	 *
	 * @return void
	 */
	protected function _postUpdate()
	{
		(new \Wishlist\Wishlist())->updateInfo($this->wishlist_id);
	}

	/**
	 * Allows post-delete logic to be applied to row.
	 * Subclasses may override this method.
	 *
	 * @return void
	 */
	protected function _postDelete()
	{ 
		(new \Wishlist\Wishlist())->updateInfo($this->wishlist_id);
	}
    
}