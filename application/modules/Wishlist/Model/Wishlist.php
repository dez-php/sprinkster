<?php

namespace Wishlist;

class Wishlist extends \Base\Model\Reference {
	
	public function __construct($config = array()) {
		parent::__construct($config);
		$this->setRowClass('\Wishlist\WishlistRow');
	}
	
	protected $_referenceMap    = array(
			'Category' => array(
					'columns'           => 'category_id',
					'refTableClass'     => 'Category\Category',
					'referenceMap'		=> array(
						'columns'           => 'id',
						'refTableClass'     => 'Category\CategoryDescription',
						'refColumns'        => 'category_id',
						'where'				=> '"language_id = " . \Core\Base\Action::getModule(\'Language\')->getLanguageId()'
					),
					'refColumns'        => 'id'
			),
			'Pin' => array(
					'columns'           => 'id',
					'refTableClass'     => 'Pin\PinRepin',
					'refColumns'        => 'wishlist_id'
			),
			'User' => array(
					'columns'           => 'user_id',
					'refTableClass'     => 'User\User',
					'refColumns'        => 'id'
			),
			'Share' => array(
					'columns'           => 'id',
					'refTableClass'     => 'Wishlist\WishlistShare',
					'refColumns'        => 'wishlist_id'
			),
	);
	

	public static function getSitemap($limit = 1000000) {
		$db = \Core\Db\Init::getDefaultAdapter();
		$sql = $db->select()
					->from('wishlist', array('id','title'))
					->where('status = 1')
					->where('public = 1')
					->order('id DESC')
					->limit($limit);
		return $db->fetchPairs($sql);
	}
	
	/**
	 * Generate md5 form key
	 * @return string
	 */
	public static function getXFormCmd() {
		$request = \Core\Http\Request::getInstance();
		return md5(
				$request->getServer('HTTP_USER_AGENT')
		) . '-' . substr(md5(
				$request->getServer('SERVER_ADDR')
		), 10, 10);
		/*return md5(
			$request->getClientIp()
			. $request->getServer('HTTP_USER_AGENT')
			. \Core\Session\Base::sid()
			. $request->getServer('SERVER_ADDR')
		) . '-' . substr(md5(
			\Core\Session\Base::sid()
			. $request->getServer('SERVER_ADDR')
		), 0, 10);*/
	}
	
	//////////////////////////////
	public function getAll($filter = null, $order, $limit, $offset) {
		$db = \Core\Db\Init::getDefaultAdapter();
		
		$rows_wishlists = array('*');
		$self = \User\User::getUserData();
		if($self->id) {
			$rows_wishlists['following_wishlist'] = new \Core\Db\Expr('(('.$db->select()->from('user_follow', 'COUNT(1)')->where('user_id = ?', $self->id)->where('follow_id = wishlist.user_id')->limit(1).')+('.$db->select()->from('wishlist_follow','COUNT(1)')->where('user_id = ?', $self->id)->where('follow_id = wishlist.user_id')->where('wishlist_id = wishlist.id')->limit(1) .') - ('.$db->select()->from('wishlist_follow_ignore','COUNT(1)')->where('user_id = ?', $self->id)->where('follow_id = wishlist.user_id')->where('wishlist_id = wishlist.id')->limit(1) .'))');
			$rows_wishlists['following_user'] = new \Core\Db\Expr('(('.$db->select()->from('user_follow', 'COUNT(1)')->where('user_id = ?', $self->id)->where('follow_id = wishlist.user_id')->limit(1).')+('.$db->select()->from('wishlist_follow','COUNT(1)')->where('user_id = ?', $self->id)->where('follow_id = wishlist.user_id')->where('wishlist_id = wishlist.id')->limit(1) .'))');
		} else {
			$rows_wishlists['following_wishlist'] = new \Core\Db\Expr("0");
			$rows_wishlists['following_user'] = new \Core\Db\Expr("0");
		}
		
		$rows_wishlists['shared_wishlist'] = new \Core\Db\Expr("(SELECT COUNT(id) FROM wishlist_share WHERE wishlist_id = wishlist.id AND accept = 1 LIMIT 1)");
		if($self->id) {
			$rows_wishlists['self_shared_wishlist'] = new \Core\Db\Expr("(SELECT COUNT(id) FROM wishlist_share WHERE wishlist_id = wishlist.id AND share_id = " . $this->getAdapter()->quote($self->id) . " AND accept = 1 LIMIT 1)");
		} else {
			$rows_wishlists['self_shared_wishlist'] = new \Core\Db\Expr("0");
		}
		
		$sql = $db->select()
					->from('wishlist', $rows_wishlists)
					->joinLeft('user', 'wishlist.user_id=user.id',array('username','firstname', 'lastname','user_pins'=>'pins','wishlists','user_likes' => 'likes','user_followers'=>'followers','avatar_width', 'avatar_height', 'avatar', 'avatar_store_host', 'avatar_store'))
					->order($order)
					->where('wishlist.status = 1')
					->where('IF(user_id = '.(int)$self->id.',1, public) = 1')
					->limit($limit, $offset);
				if($filter) {
					$sql->where($filter);
				} 
		
		if(in_array('secret', $this->info('cols'))) {
			/////////////////// secret
			$self_data = \User\User::getUserData();
			if($self_data->id) {
				$sql->where('IF(wishlist.secret = 0, 1, IF(wishlist.user_id = ?,1, (SELECT COUNT(id) FROM wishlist_share WHERE wishlist_id = wishlist.id AND share_id = ? LIMIT 1)))',$self_data->id);
			} else {
				$sql->where('wishlist.secret = 0');
			}
			//////////////////////////
		}
		
		//3.2 wishlist type
		$sql->joinLeft('wishlist_type', 'wishlist.id=wishlist_type.wishlist_id', array('type','module','controller','action'));
		
		//extend wishlist query
		$extendTable = new \Base\Extend();
		$extends = $extendTable->getExtension('Wishlist\getAll', 'order');
		if($extends) {
			$front = \Core\Base\Front::getInstance();
			foreach($extends AS $extend) {
				$objectName = $front->formatHelperName($extend->extend);
				try {
					if(class_exists($objectName)) {
						$object = new $objectName($sql, $extend, $this);
						if($object instanceof \Wishlist\WishlistOrderAbstract) {
							$sql = $object->getExtendetSql();
						}
					}
				} catch (\Core\Exception $e) {}
			}
		}
		//end extend wishlist query
		
		$rows = $db->fetchAll($sql);

        $data  = array(
            'table'    => $this,
            'data'     => $rows,
            'readOnly' => true,
            'rowClass' => $this->getRowClass(),
            'stored'   => true
        );

        $rowsetClass = $this->getRowsetClass();
        if (!class_exists($rowsetClass)) {
            require_once 'Loader.php';
            \Core\Loader\Loader::loadClass($rowsetClass);
        }

        return new $rowsetClass($data);
	}
	
	public function get($id) {
		$db = \Core\Db\Init::getDefaultAdapter();
		
		$rows_wishlists = array('*');
		$self = \User\User::getUserData();
		if($self->id) {
			$rows_wishlists['following_wishlist'] = new \Core\Db\Expr('(('.$db->select()->from('user_follow', 'COUNT(1)')->where('user_id = ?', $self->id)->where('follow_id = wishlist.user_id')->limit(1).')+('.$db->select()->from('wishlist_follow','COUNT(1)')->where('user_id = ?', $self->id)->where('follow_id = wishlist.user_id')->where('wishlist_id = wishlist.id')->limit(1) .') - ('.$db->select()->from('wishlist_follow_ignore','COUNT(1)')->where('user_id = ?', $self->id)->where('follow_id = wishlist.user_id')->where('wishlist_id = wishlist.id')->limit(1) .'))');
			$rows_wishlists['following_user'] = new \Core\Db\Expr('(('.$db->select()->from('user_follow', 'COUNT(1)')->where('user_id = ?', $self->id)->where('follow_id = wishlist.user_id')->limit(1).')+('.$db->select()->from('wishlist_follow','COUNT(1)')->where('user_id = ?', $self->id)->where('follow_id = wishlist.user_id')->where('wishlist_id = wishlist.id')->limit(1) .'))');
		} else {
			$rows_wishlists['following_wishlist'] = new \Core\Db\Expr("0");
			$rows_wishlists['following_user'] = new \Core\Db\Expr("0");
		}
		
		$rows_wishlists['shared_wishlist'] = new \Core\Db\Expr("(SELECT COUNT(id) FROM wishlist_share WHERE wishlist_id = wishlist.id AND accept = 1 LIMIT 1)");
		if($self->id) {
			$rows_wishlists['self_shared_wishlist'] = new \Core\Db\Expr("(SELECT COUNT(id) FROM wishlist_share WHERE wishlist_id = wishlist.id AND share_id = " . $this->getAdapter()->quote($self->id) . " AND accept = 1 LIMIT 1)");
		} else {
			$rows_wishlists['self_shared_wishlist'] = new \Core\Db\Expr("0");
		}
		
		$sql = $db->select()
					->from('wishlist', $rows_wishlists)
					->where('wishlist.status = 1')
					->where('IF(wishlist.user_id = '.(int)$self->id.',1, wishlist.public) = 1')
					->where('wishlist.id = ?', $id)
					->limit(1);
		
		if(in_array('secret', $this->info('cols'))) {
			/////////////////// secret
			$self_data = \User\User::getUserData();
			if($self_data->id) {
				$sql->where('IF(wishlist.secret = 0, 1, IF(wishlist.user_id = ?,1, (SELECT COUNT(id) FROM wishlist_share WHERE wishlist_id = wishlist.id AND share_id = ? LIMIT 1)))',$self_data->id);
			} else {
				$sql->where('wishlist.secret = 0');
			}
			//////////////////////////
		}

		//3.2 wishlist type
		$sql->joinLeft('wishlist_type', 'wishlist.id=wishlist_type.wishlist_id', array('type','module','controller','action'));
		
		//extend wishlist query
		$extendTable = new \Base\Extend();
		$extends = $extendTable->getExtension('Wishlist\getAll', 'order');
		if($extends) {
			$front = \Core\Base\Front::getInstance();
			foreach($extends AS $extend) {
				$objectName = $front->formatHelperName($extend->extend);
				try {
					if(class_exists($objectName)) {
						$object = new $objectName($sql, $extend, $this);
						if($object instanceof \Wishlist\WishlistOrderAbstract) {
							$sql = $object->getExtendetSql();
						}
					}
				} catch (\Core\Exception $e) {}
			}
		}
		//end extend wishlist query
				
		$rows = $db->fetchRow($sql);

        if (!$rows) {
            return null;
        }

        $data = array(
            'table'   => $this,
            'data'     => $rows,
            'readOnly' => true,
            'stored'  => true
        );

        $rowClass = $this->getRowClass();
        if (!class_exists($rowClass)) {
            require_once 'Loader.php';
            \Core\Loader\Loader::loadClass($rowClass);
        }
        return new $rowClass($data);
	}
	
	public function countWishlists($filter = null) {
		$db = \Core\Db\Init::getDefaultAdapter();
		
		$rows_wishlists = array('COUNT(DISTINCT wishlist.id) AS total');
		$self = \User\User::getUserData();
		
		$sql = $db->select()
					->from('wishlist', $rows_wishlists)
					->joinLeft('user', 'wishlist.user_id=user.id','')
					->where('wishlist.status = 1')
					->where('IF(user_id = '.(int)$self->id.',1, public) = 1')
					->limit(1);
				if($filter) {
					$sql->where($filter);
				} 
		
		if(in_array('secret', $this->info('cols'))) {
			/////////////////// secret
			$self_data = \User\User::getUserData();
			if($self_data->id) {
				$sql->where('IF(wishlist.secret = 0, 1, IF(wishlist.user_id = ?,1, (SELECT COUNT(id) FROM wishlist_share WHERE wishlist_id = wishlist.id AND share_id = ? LIMIT 1)))',$self_data->id);
			} else {
				$sql->where('wishlist.secret = 0');
			}
			//////////////////////////
		}
		
		//3.2 wishlist type
		$sql->joinLeft('wishlist_type', 'wishlist.id=wishlist_type.wishlist_id', array('type','module','controller','action'));
		
		//extend wishlist query
		$extendTable = new \Base\Extend();
		$extends = $extendTable->getExtension('Wishlist\getAll', 'order');
		if($extends) {
			$front = \Core\Base\Front::getInstance();
			foreach($extends AS $extend) {
				$objectName = $front->formatHelperName($extend->extend);
				try {
					if(class_exists($objectName)) {
						$object = new $objectName($sql, $extend, $this);
						if($object instanceof \Wishlist\WishlistOrderAbstract) {
							$sql = $object->getExtendetSql();
						}
					}
				} catch (\Core\Exception $e) {}
			}
		}
		//end extend wishlist query
		
		return $db->fetchOne($sql);
	}
	
	/**
	 * @param number $pin_id
	 * @return multitype:unknown multitype:NULL  |NULL
	 */
	public static function getInfo($wishlist_id) {
		$self = new self();
		$wishlist = $self->fetchRow(array('id = ?' => $wishlist_id));
		if($wishlist) {
			return array(
				'id' => $wishlist_id,
				'stats' => array(
					'pins' => $wishlist->pins,
					'followers' => $wishlist->followers		
				)		
			);
		}
		return null;
	}
	
	public function updateInfo ($wishlist_id) {
		$pinTable = new \Pin\PinRepin();
		return $this->update(array(
			'pins' => $pinTable->countByWishlistId($wishlist_id),
			'followers' => new \Core\Db\Expr('( (SELECT COUNT(DISTINCT user_id) FROM user_follow WHERE follow_id = wishlist.user_id AND user_id != wishlist.user_id AND status = 1 LIMIT 1) + (SELECT COUNT(DISTINCT user_id) FROM wishlist_follow WHERE wishlist_id = wishlist.id AND follow_id = wishlist.user_id AND user_id != wishlist.user_id AND status = 1 LIMIT 1) - (SELECT COUNT(DISTINCT user_id) FROM wishlist_follow_ignore WHERE follow_id = wishlist.user_id AND wishlist_id = wishlist.id AND user_id != wishlist.user_id AND status = 1 LIMIT 1) )')
		), array('id = ?' => $wishlist_id));
	}
	
	/**
	 * Fetches all rows.
	 * @return \Core\Db\Table\Rowset\AbstractRowset 
	 */
	public function getWithShared($user_id, $wishlist_id = null) {
		$db = \Core\Db\Init::getDefaultAdapter();
		
		$sharedTable = new \Wishlist\WishlistShare();
		$shared = $sharedTable->select()
								->from($sharedTable,'wishlist_id')
								->where('accept = 1')
								->where('share_id = ?');
		$sql = $db->select()
					->from('wishlist')
					->where('wishlist.user_id = ? OR wishlist.id IN (' . $shared . ')', $user_id)
					->where('wishlist.status = 1')
					->order(new \Core\Db\Expr('FIELD(wishlist.user_id,'.$user_id.') DESC, wishlist.title ASC'));
		/*if($this->_order) {
			$sql->order($this->_order);
		}*/
		if($wishlist_id !== null) {
			$sql->where('wishlist.id = ?', $wishlist_id);
		}
		
		if(in_array('secret', $this->info('cols'))) {
			/////////////////// secret
			$self_data = \User\User::getUserData();
			if($self_data->id) {
				$sql->where('IF(wishlist.secret = 0, 1, IF(wishlist.user_id = ?,1, (SELECT COUNT(id) FROM wishlist_share WHERE wishlist_id = wishlist.id AND share_id = ? LIMIT 1)))',$self_data->id);
			} else {
				$sql->where('wishlist.secret = 0');
			}
			//////////////////////////
		}

		//3.2 wishlist type
		$sql->joinLeft('wishlist_type', 'wishlist.id=wishlist_type.wishlist_id', array('type','module','controller','action'));
		
		//extend wishlist query
		$extendTable = new \Base\Extend();
		$extends = $extendTable->getExtension('Wishlist\getAll', 'order');
		if($extends) {
			$front = \Core\Base\Front::getInstance();
			foreach($extends AS $extend) {
				$objectName = $front->formatHelperName($extend->extend);
				try {
					if(class_exists($objectName)) {
						$object = new $objectName($sql, $extend, $this);
						if($object instanceof \Wishlist\WishlistOrderAbstract) {
							$sql = $object->getExtendetSql();
						}
					}
				} catch (\Core\Exception $e) {}
			}
		}
		//end extend wishlist query
		
		$rows = $db->fetchAll($sql);

        $data  = array(
            'table'    => $this,
            'data'     => $rows,
            'readOnly' => true,
            'rowClass' => $this->getRowClass(),
            'stored'   => true
        );

        $rowsetClass = $this->getRowsetClass();
        if (!class_exists($rowsetClass)) {
            require_once 'Loader.php';
            \Core\Loader\Loader::loadClass($rowsetClass);
        }

        return new $rowsetClass($data);
	}
	
	public static function getWithSharedCallback($user_id) {
		$self = new self();
		$sharedTable = new \Wishlist\WishlistShare();
		$shared = $sharedTable->select()
							->from($sharedTable,'wishlist_id')
							->where('accept = 1')
							->where('share_id = ?');
		$sql = $self->select()
					->from($self,'id')
					->where('user_id = ? OR id IN (' . $shared . ')', $user_id)
					->where('status = 1');
		
		if(in_array('secret', $self->info('cols'))) {
			/////////////////// secret
			$self_data = \User\User::getUserData();
			if($self_data->id) {
				//$sql->where('(secret = 0 OR user_id = ? OR (SELECT COUNT(id) FROM wishlist_share WHERE wishlist_id = wishlist.id AND share_id = ? LIMIT 1))',$self_data->id);
				$sql->where('IF(secret = 0, 1, IF(user_id = ?,1, (SELECT COUNT(id) FROM wishlist_share WHERE wishlist_id = wishlist.id AND share_id = ? LIMIT 1)))',$self_data->id);
			} else {
				$sql->where('secret = 0');
			}
			//////////////////////////
		}
		
		return $sql;
	}
	
	public static function sortOrder($user_id) {
		return array($user_id);
	}
	
	public function extendDelete($wishlist_id, $callback = null) {
		$this->getAdapter()->beginTransaction();
		try {
			
			$sql = $this->select()
					->from($this,array('image'=>'cover','store'=>'cover_store','group' => new \Core\Db\Expr('"wishlistCovers"')))
					->where('id = ?', $wishlist_id);
			
			(new \Base\ImageDelete())->insertSelect($sql);
			
			$wishlist = $this->fetchRow(array('id = ?' => $wishlist_id));
			$rows = $wishlist->delete();
			if($callback && is_callable($callback)) {
				call_user_func_array($callback, array($this, $rows, $wishlist_id));
			}
			
			$this->getAdapter()->commit();
			return $rows;
		} catch (\Core\Db\Exception $e) {
			$this->getAdapter()->rollBack();
			throw new \Core\Exception($e->getMessage());
		}
	}

}