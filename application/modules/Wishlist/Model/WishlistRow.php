<?php

namespace Wishlist;

class WishlistRow extends \Core\Db\Table\Row {
	
	/**
	 * Allows pre-insert logic to be applied to row.
	 * Subclasses may override this method.
	 *
	 * @return void
	 */
	protected function _insert()
	{
		$this->date_added = \Core\Date::getInstance(null,\Core\Date::SQL_FULL, true)->toString();
		$this->date_modified = $this->date_added;
	}
	
	/**
	 * Allows pre-update logic to be applied to row.
	 * Subclasses may override this method.
	 *
	 * @return void
	 */
	protected function _update()
	{
		$this->date_modified = \Core\Date::getInstance(null,\Core\Date::SQL_FULL, true)->toString();
	}

    /**
     * Allows post-insert logic to be applied to row.
     * Subclasses may override this method.
     *
     * @return void
     */
    protected function _postInsert()
    {
    	$userTable = new \User\User();
    	$wishlistTable = new \Wishlist\Wishlist();
    	
    	$userTable->updateInfo($this->user_id);
    	$wishlistTable->updateInfo($this->id);
    }

    /**
     * Allows post-update logic to be applied to row.
     * Subclasses may override this method.
     *
     * @return void
     */
    protected function _postUpdate()
    {
    	$pinTable = new \Pin\Pin();
    	$userTable = new \User\User();
    	$wishlistTable = new \Wishlist\Wishlist();
    	if(array_key_exists('category_id', $this->_modifiedFields)) {
    		$pinTable->update(array('category_id' => $this->category_id), array(
    			'wishlist_id = ?' => $this->id		
    		));
    	}
    	
    	$userTable->updateInfo($this->user_id);
    	$wishlistTable->updateInfo($this->id);
    }

    /**
     * Allows post-delete logic to be applied to row.
     * Subclasses may override this method.
     *
     * @return void
     */
    protected function _postDelete()
    {
    	$userTable = new \User\User();
    	$userTable->updateInfo($this->user_id);
    }
    
    public function getUserFullname() {
    	$username_method_show = \Base\Config::get('username_show');
    	if($username_method_show == 'fullname') {
    		return $this->firstname . ' ' . $this->lastname;
    	} else if($username_method_show == 'fullname-desc') {
    		return $this->lastname . ' ' . $this->firstname;
    	} else if($username_method_show == 'firstname') {
    		return $this->firstname;
    	} else {
    		return $this->username;
    	}
    }
	
	

}