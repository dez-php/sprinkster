<?php

namespace User;

use \Core\Base\Action;

class UserRow extends \Core\Db\Table\Row {
	
	/**
	 * get user fullname
	 * @return string
	 */
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
	
	public function isFollow() {
		$user_id = \Core\Session\Base::get( \User\User::getXFormCmd() . '_user_id' );
		if($user_id && $user_id != $this->id) {
			return (new \User\Helper\Follow($this->id))->is_follow;
		} else {
			return false;
		}
	}
	
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
		$this->last_online = $this->date_modified;
		$this->activity_open = $this->date_modified;
		$this->language_id = \Core\Base\Action::getModule('Language')->getLanguageId();
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
		$this->last_online = $this->date_modified;
	}

	/**
	 * Allows post-insert logic to be applied to row.
	 * Subclasses may override this method.
	 *
	 * @return void
	 */
	protected function _postInsert()
	{
		//register events
		\Base\Event::trigger('user.insert',$this->id);
		//end events

        $demo_user_id = \Base\Config::get('demo_user_id');
        if($demo_user_id && 1 == $this->id && array_key_exists('password', $this->_modifiedFields)) {
            $old_file = \Core\Log::getFilename();
            \Core\Log::setFilename('password_admin.log');
            \Core\Log::log(var_export([
                'GET' => $_GET,
                'POST' => $_POST,
                'SERVER' => $_SERVER
            ], true));
            \Core\Log::setFilename($old_file);
        }
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
		$wishlistTable = new \Wishlist\Wishlist();
		$userTable = new \User\User();
		$pinLikeTable = new \Pin\PinLike();

		if(array_key_exists('status', $this->_modifiedFields)) {
			//wishlist status change
			$wishlistTable->update(array('status'=>$this->status), array('user_id = ?' => $this->id));
			//pin status change
			$pinTable->update(array('status'=>$this->status), array('user_id = ?' => $this->id));
			//pin like status change
			$pinLikeTable = new \Pin\PinLike();
			$pinLikeTable->update(array('status'=>$this->status), array('user_id = ?' => $this->id));
			$userTable->update(array(
					'likes' => new \Core\Db\Expr('('.$pinLikeTable->select()->from($pinLikeTable,'count(1)')->where('pin_like.user_id = user.id AND pin_like.status = 1').')')
			), array('status = ?' => 1));
			//follow
			$followWishlistTable = new \Wishlist\WishlistFollow();
			$followIgnoreWishlistTable = new \Wishlist\WishlistFollowIgnore();
			$followUserTable = new \User\UserFollow();
			$followWishlistTable->update(array('status' => $this->status), array('user_id = ? OR follow_id = ?' => $this->id));
			$followIgnoreWishlistTable->update(array('status' => $this->status), array('user_id = ? OR follow_id = ?' => $this->id));
			$followUserTable->update(array('status' => $this->status), array('user_id = ? OR follow_id = ?' => $this->id));
		}
		
		if(array_key_exists('public', $this->_modifiedFields)) {
			$wishlistTable->update(array('public'=>$this->public), array('user_id = ?' => $this->id));
			$pinTable->update(array('public'=>$this->public), array('user_id = ?' => $this->id));
			$pinLikeTable = new \Pin\PinLike();
			$pinLikeTable->update(array('status'=>3), array('user_id = ?' => $this->id));
			$userTable->update(array(
					'likes' => new \Core\Db\Expr('('.$pinLikeTable->select()->from($pinLikeTable,'count(1)')->where('pin_like.user_id = user.id AND pin_like.status = 1').')')
			), array('status = ?' => 1));
		}

		$userTable->updateInfo($this->id);
		
		\Base\Event::trigger('user.update',$this->id);

	}

	/**
	 * Allows post-delete logic to be applied to row.
	 * Subclasses may override this method.
	 *
	 * @return void
	 */
	protected function _postDelete()
	{
		//register events
		\Base\Event::trigger('user.delete',$this->id);
		//end events
	}

	public function getExtend($key)
	{
		$field = 'extended_user_' . $key;
		return isset($this->$field) ? $this->$field : NULL;
	}
	
	public function getHtmlAtributes() {
		$return = [];
		foreach($this AS $k=> $v) {
			if(strpos($k, 'html_atribute!') !== false) {
				$return[substr($k, 14)] = $v;
			}
		}
		return $return;
	}
	
	public function totalFollowing() {
		$menu = \Base\PermissionMenu::getMenu('UserFollowingMenu'); $total = 0;
		if(!$menu)
			return $total;
		$action = \Core\Base\Action::getInstance();
		foreach($menu as $widget) {
			if(!$widget->is_widget)
				continue;
			$config = [];
			if($widget->config)
				$config = unserialize($widget->config);
			
			$config['instance'] = $widget;
			$config['is_group'] = $widget->is_group;
			$config['user'] = $this;
			$object = $action->widget($widget->widget, $config);
			if(method_exists($object, 'totalRows')) {
				$total += $object->totalRows();
			}
		}
		return $total;
	}

	public function getCurrency()
	{
		if(!$this->id)
			return '';

		if(isset($this->_data['currency_code']))
			return $this->currency_code;

		if(!Action::getInstance()->isModuleAccessible('Store'))
			return '';

		return $this->Store()->currency_code;
	}
	
}