<?php

namespace Pin;

class PinRow extends \Core\Db\Table\Row {
	
	public function getTable()
	{
		return parent::getTable() ?: new \Pin\Pin;
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
		if($this->source_id) {
			$media = new \Media\Helper\UrlInfo();
			if($media->parseUrl($this->from)) {
				$this->video = 1;
			}
		}
		//v4
		if($this->source_id) {
			$this->md5_from = md5($this->from);
		} else {
			$this->md5_from = null;
		}
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
		if(array_key_exists('from', $this->_modifiedFields)) {
			if($this->source_id) {
				$media = new \Media\Helper\UrlInfo();
				if($media->parseUrl($this->from)) {
					$this->video = 1;
				} else {
					$this->video = 0;
				}
			} else {
				$this->video = 0;
			}
		}
		//v4
		if($this->source_id) {
			$this->md5_from = md5($this->from);
		} else {
			$this->md5_from = null;
		}
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
		
		if($this->parent_id) {
			$repin = $this->getTable()->fetchRow(array('id = ?' => $this->parent_id, 'status' => 1 ));
			if($repin) {
				$pinTable = new \Pin\Pin();
				$pinTable->updateInfo($this->parent_id);
			}
		}
		if($this->source_id) {
			$sourceTable = new \Source\Source();
			$source = $sourceTable->fetchRow(array('id = ?' => $this->source_id));
			if($source) {
				$source->pins = $this->getTable()->countBySourceId_Status($this->source_id,1);
				$source->save();
			}
		}
		$user = $userTable->fetchRow(array('id = ?' => $this->user_id));
		if($user) {
			$userTable->updateInfo($this->user_id);
		}
		
		if($this->category_id) {
			$categoryTable = new \Category\Category();
			$category = $categoryTable->fetchRow(array('id = ?' => $this->category_id));
			if($category) {
				$category->date_modified = $this->date_added;
				$category->save();
			}
		}
		
		//register events
		\Base\Event::trigger('pin.insert',$this->id);
		if($this->image && array_key_exists('image', $this->_modifiedFields)) {
			\Base\Event::trigger('pin.uploadImage',$this->id);
		}
		//end events
		
	}

	/**
	 * Allows post-update logic to be applied to row.
	 * Subclasses may override this method.
	 *
	 * @return void
	 */
	protected function _postUpdate()
	{

		$userTable = new \User\User();
		if(array_key_exists('status', $this->_modifiedFields)) {
			$pinLikeTable = new \Pin\PinLike();
			$userTable->update(array(
					'likes' => $pinLikeTable->countByUserId_Status($this->user_id,1)
			), array('id=?'=>$this->user_id));
		}
		
		if($this->source_id) {
			$sourceTable = new \Source\Source();
			$source = $sourceTable->fetchRow(array('id = ?' => $this->source_id));
			if($source) {
				$source->pins = $this->getTable()->countBySourceId_Status($this->source_id,1);
				$source->save();
			}
		}
		$user = $userTable->fetchRow(array('id = ?' => $this->user_id));
		if($user) {
			$userTable->updateInfo($this->user_id);
		}
		
		//register events
		if(array_key_exists('image', $this->_modifiedFields)) {
			\Base\Event::trigger('pin.uploadImage',$this->id);
		}
		\Base\Event::trigger('pin.update',$this->id);
		//end events
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
		
		if($this->parent_id) {
			$repin = $this->getTable()->fetchRow(array('id = ?' => $this->parent_id, 'status' => 1 ));
			if($repin) {
				$pinTable = new \Pin\Pin();
				$pinTable->updateInfo($this->parent_id);
			}
		}
		if($this->source_id) {
			$sourceTable = new \Source\Source();
			$source = $sourceTable->fetchRow(array('id = ?' => $this->source_id));
			if($source) {
				$source->pins = $this->getTable()->countBySourceId_Status($this->source_id,1);
				$source->save();
			}
		}
		$user = $userTable->fetchRow(array('id = ?' => $this->user_id));
		if($user) {
			$userTable->updateInfo($this->user_id);
		}
		
		//register events
		\Base\Event::trigger('pin.delete',$this->id);
		//end events
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
	
	public function getPrice() {
		if($this->price) {
			return \Currency\Helper\Format::format($this->price, \Currency\Helper\Format::setCode($this->currency_code) );
		}
		return null;
	}

	public function getExtend($key)
	{
		$field = 'extended_pin_' . $key;
		return isset($this->$field) ? $this->$field : NULL;
	}
    
    public function getHtmlAtributes() {
    	
    	static $transpate = null, $action = null;
    	if($transpate === null)
    		$transpate = new \Translate\Locale('Front\\'.__NAMESPACE__, \Core\Base\Action::getModule('Language')->getLanguageId());
    	if($action === null)
    		$action = \Core\Base\Action::getInstance();
    	
    	$date = new \Date\Helper\Diff($this->date_added, time());
    	$user = isset($this->avatar_width) ? $this : $this->User();
    	$media = new \Media\Helper\UrlInfo;
    	$video = $this->video && $media->parseUrl($this->from);
    	$media->setWidth(800);
    	$media->setHeight(600);
    	$saved_in_wishlist = '';
    	$self = \User\User::getUserData();
		if($self->id) {
			$repin = new \Pin\PinRepin;
			$wishlist = $repin->fetchRow([ 'user_id = ?' => $self->id, 'pin_id = ?' => $this->id]);
			if($wishlist){
	    		$wishlist = (new \Wishlist\Wishlist())->get($wishlist->wishlist_id);
	    		$url = $action->url([ 'wishlist_id' => $wishlist->id, 'query' => $action->urlQuery($wishlist->title) ], 'wishlist');
	    		$saved_in_wishlist = '<a href="' . $url . '">' . sprintf($transpate->_('Saved to %s'), $wishlist->title) . '</a>';
	    	}
		}
		
    	$return = [
    		'pindat' => json_encode([
    			'id' => $this->id,
	    		'images' => \Pin\Helper\Image::getImages($this),
	    		'title' => $this->title,
    			'uploaded' => sprintf($transpate->_('%s %d %s ago'), $transpate->_($this->pinned_from),$date->getSingle()->value, $transpate->_($date->getSingle()->key)),
    			'saved_in_wishlist' => $saved_in_wishlist,
    			'user' => [
    				'id' => $this->user_id,
    				'url' => $action->url(['user_id' => $this->user_id,'query' => $user->username],'user'),
    				'avatars' => \User\Helper\Avatar::getImages($user),
    				'fullname' => $user->getUserFullname()
    			],
    			'video' => $video,
    			'video_html' => $video ? $media->getEmbedCode() : false,
				'source_id' => $this->source_id,
    			'source' => $this->source_id ? [
    				'name' => $this->Source()->name,
    				'redirect_from' => $this->redirect_from,
    				'pinned' => sprintf($transpate->_('Pinned from %s'), $this->Source()->name)
    			] : null,
    			'i18n' => [
    				'prev' => $transpate->_('Prev pin'),
    				'next' => $transpate->_('Next pin'),
    			]
    		])
    	];
    	foreach($this AS $k=> $v) {
    		if(strpos($k, 'html_atribute!') !== false) {
    			$return[substr($k, 14)] = $v;
    		}
    	}
    	return $return;
    }
    
    public function getImage($size, $no_image = true) {
    	try
    	{
    		$thumb = \Pin\Helper\Image::getImage($size, $this, $no_image);
    	}
    	catch(\Exception $e)
    	{
    		if($no_image) {
	    		try {
	    			$thumb = \Pin\Helper\Image::noImage($size);
	    		} catch (\Exception $e) {
	    			$thumb = new \stdClass();
	    			$thumb->image = null;
	    		}
    		} else {
    			$thumb = new \stdClass();
    			$thumb->image = null;
    		}
    		if(!isset($thumb->width)) {
    			$sizes = null;
    			try {
    				$sizes = \Pin\Helper\Image::getSizesData($size);
    			} catch (\Exception $e) {
    				
    			}
    			if(!$sizes) {
    				$thumb->width = 0;
    				$thumb->height = 0;
    			} else {
	    			if($sizes['width'] && $sizes['height']) {
	    				$thumb->width = (int)min($sizes['width'],$this->width);
	    				$thumb->height = (int)min($sizes['height'],$this->height);
	    			} else if(!$sizes['width'] && $sizes['height']) {
	    				$thumb->height = (int)min($sizes['height'],$this->height);
	    				$thumb->width = (int)ceil($this->width / ($this->height/min($sizes['height'],$this->height)));
	    			} else if($sizes['width'] && !$sizes['height']) {
	    				$thumb->width = (int)min($sizes['width'],$this->width);
	    				$thumb->height = (int)ceil($this->height / ($this->width/min($sizes['width'],$this->width)));
	    			} else {
	    				$thumb->width = 0;
	    				$thumb->height = 0;
	    			}
    			}
    		}
    	}
    	return $thumb;
    }

    public function getType()
    {
    	return $this->module ?: NULL;
    }
    
}