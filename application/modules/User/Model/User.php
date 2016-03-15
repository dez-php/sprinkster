<?php

namespace User;

class User extends \Base\Model\Reference {
	
	public function __construct($config = array()) {
		parent::__construct($config);
		$this->setRowClass('\User\UserRow');
	}
	
	protected $_referenceMap    = array(
			'Pin' => array(
					'columns'           => 'id',
					'refTableClass'     => 'Pin\Pin',
					'refColumns'        => 'user_id',
					'singleRow'			=> true
			),
			'Wishlist' => array(
					'columns'           => 'id',
					'refTableClass'     => 'Wishlist\Wishlist',
					'refColumns'        => 'user_id'
			),
			'Cart' => array(
					'columns'           => 'id',
					'refTableClass'     => 'Cart\ServiceCart',
					'refColumns'        => 'user_id'
			),
			'Buyer' => array(
					'columns'           => 'id',
					'refTableClass'     => 'Paymentgateway\Purchase',
					'refColumns'        => 'user_id',
			),
			'Seller' => array(
					'columns'           => 'id',
					'refTableClass'     => 'Paymentgateway\Purchase',
					'refColumns'        => 'seller_id',
			),
			'TransactionManager' => array(
					'columns'           => 'id',
					'refTableClass'     => 'Wallet\TransactionManager',
					'refColumns'        => 'user_id',
			),
            'Country' => array(
                'columns'           => 'country_iso_code_3',
                'refTableClass'     => 'Country\Country',
                'refColumns'        => 'iso_code_3',
            ),
	);
	
	private $reserved = [];
	
	/////////////////// custom methods
	
	static $instance_user_data = array();
	
	private $error = null;
	
	const USER_NOT_FOUND 	= 'not-found';
	const WRONG_PASSWORD 	= 'wrong-password';
	const USER_NOT_ACTIVE 	= 'user-not-active';
	
	/**
	 * get user info by session stored id
	 * 
	 * @return multitype:|\Core\Db\Table\Row\Abstract
	 */
	public static function getUserData() {
		$user_id = \Core\Session\Base::get( self::getXFormCmd() . '_user_id' );
		if(isset(self::$instance_user_data[$user_id])) {
			return self::$instance_user_data[$user_id];
		} else {
			$instance = new self();
			if((int)$user_id) {
				$user_data = $instance->get($user_id);
				if(!$user_data) {
					$user_data = /*$instance->emptyUserData();*/$instance->fetchNew();
				}
			} else {
				$user_data = /*$instance->emptyUserData();*/$instance->fetchNew();
			}
			\Core\Session\Base::set( array('user_data' => $user_data) );
			self::$instance_user_data[$user_id] = $user_data;
			return $user_data;
		}
	}
	
	/**
	 * @return \stdClass
	 */
	private function emptyUserData() {
		$cols = array_values($this->_getCols());
		$object = new \stdClass();
		foreach($cols AS $col) {
			$object->{$col} = null;
		}
		return $object;
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
		), 5, 10);
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
	
	/**
	 * Validate user login
	 * 
	 * @return boolean
	 */
	public function validateLogin($email, $password) { 
		$user_data = $this->fetchRow(array('email = ?' => $email));
		if($user_data) {
			if($user_data->status != 1) {
				$this->error = self::USER_NOT_ACTIVE;
				return false;
			} else if($user_data->password == md5($password)) {
				\Core\Session\Base::set( self::getXFormCmd() . '_user_id', $user_data->id );
				return $user_data->id;
			} else {
				$this->error = self::WRONG_PASSWORD;
				return false;
			}
		} else {
			$this->error = self::USER_NOT_FOUND;
		}
		return false;
	}
	
	/**
	 * @param string $email
	 * @return Ambigous <\Core\Db\Table\Row\AbstractRow, NULL, unknown>|boolean
	 */
	public function validateForgottenPassword($email) {
		$user_data = $this->fetchRow(array('email = ?' => $email));
		if($user_data) {
			if($user_data->status != 1) {
				$this->error = self::USER_NOT_ACTIVE;
			} else {
				return $user_data;
			}
		} else {
			$this->error = self::USER_NOT_FOUND;
		}
		return false;
	}
	
	/**
	 * Validate admin login
	 * 
	 * @return boolean
	 */
	public function validateLoginAdmin($email, $password) { 
		$user_data = $this->fetchRow(array('email = ?' => $email));
		if($user_data) {
			if($user_data->status != 1) {
				$this->error = self::USER_NOT_ACTIVE;
				return false;
			} else if($user_data->password == md5($password)) {
				if($user_data->is_admin) {
					\Core\Session\Base::set( self::getXFormCmd() . '_user_id', $user_data->id );
					return $user_data->id;
				} else {
					$this->error = self::USER_NOT_FOUND;
					return false;
				}
			} else {
				$this->error = self::WRONG_PASSWORD;
				return false;
			}
		} else {
			$this->error = self::USER_NOT_FOUND;
			return false;
		}
		return false;
	}
	
	/**
	 * @param number $id
	 * @return boolean
	 */
	public function loginById($id) { 
		$user_data = $this->fetchRow(array('id = ?' => $id));

		if($user_data) {
			if($user_data->status != 1) {
				$this->error = self::USER_NOT_ACTIVE;
				return false;
			} else {
				\Core\Session\Base::set( self::getXFormCmd() . '_user_id', $user_data->id );
				return $user_data->id;
			}
		} else {
			$this->error = self::USER_NOT_FOUND;
		}
		return false;
	}
	
	/**
	 * @return array
	 */
	public function getErrors() {
		return $this->error;
	}
	
	/**
	 * @return boolean
	 */
	public static function logout() {
		\Core\Session\Base::clear( self::getXFormCmd() . '_user_id' );
		return true;
	}
	
	/**
	 * @param number $limit
	 * @return Ambigous <multitype:, multitype:unknown >
	 */
	public static function getSitemap($limit = 1000000) {
		$db = \Core\Db\Init::getDefaultAdapter();
		$sql = $db->select()
					->from('user', array('id','username'))
					->order('id DESC')
					->limit($limit);
		return $db->fetchPairs($sql);
	}
	
	public static function toRow($row) {
		
		if(!$row)
			return null;
		
		$self = new self();
		
		$data = array(
				'table'   => $self,
				'data'     => $row,
				'readOnly' => false,
				'stored'  => true
		);
		
		$rowClass = $self->getRowClass();
		if (!class_exists($rowClass)) {
			require_once 'Loader.php';
			\Core\Loader\Loader::loadClass($rowClass);
		}
		return new $rowClass($data);
	}
	
	public static function toRowset($rows) {
		
		$self = new self();
		
		$data  = array(
				'table'    => $self,
				'data'     => $rows,
				'readOnly' => false,
				'rowClass' => $self->getRowClass(),
				'stored'   => true
		);
		
		$rowsetClass = $self->getRowsetClass();
		if (!class_exists($rowsetClass)) {
			require_once 'Loader.php';
			\Core\Loader\Loader::loadClass($rowsetClass);
		}
		
		return new $rowsetClass($data);
	}
	
	public function getAll($filter = null, $order, $limit = null, $offset = null) {
		$db = \Core\Db\Init::getDefaultAdapter();
		
		$rows = array('*');
		$self = \User\User::getUserData();
		if($self->id) {
			$rows['following_user'] = new \Core\Db\Expr('(('.$db->select()->from('user_follow', 'COUNT(DISTINCT follow_id)')->where('user_id = ?', $self->id)->where('follow_id = user.id')->limit(1).')+('.$db->select()->from('wishlist_follow','COUNT(DISTINCT follow_id)')->where('user_id = ?', $self->id)->where('follow_id = user.id')->limit(1) .'))');
		} else {
			$rows['following_user'] = new \Core\Db\Expr("0");
		}
		
		$sql = $db->select()
					->from('user', $rows)
					->where('user.status = 1')
					->order($order)
					->limit($limit, $offset);
				if($filter) {
					$sql->where($filter);
				}
				
		//extend order and get to show label's
		$extendTable = new \Base\Extend();
		$extends = $extendTable->getExtension('User\getAll', 'order');
		if($extends) {
			$front = \Core\Base\Front::getInstance();
			foreach($extends AS $extend) {
				$objectName = $front->formatHelperName($extend->extend);
				try {
					if(class_exists($objectName)) {
						$object = new $objectName($sql, $extend, $this);
						if($object instanceof \User\UserOrderAbstract) {
							$sql = $object->getExtendetSql();
						}
					}
				} catch (\Core\Exception $e) {}
			}
		}
		
		return self::toRowset($db->fetchAll($sql));
	}
	
	public function get($id) {
		$db = \Core\Db\Init::getDefaultAdapter();
		
		$rows = array('*');
		$user_id = \Core\Session\Base::get( self::getXFormCmd() . '_user_id' );
		
		$sql = $db->select()
					->from('user', $rows)
					->where('user.id = ?', $id)
					->where('user.status = 1')
					->limit(1);
				
		//extend order and get to show label's
		$extendTable = new \Base\Extend();
		$extends = $extendTable->getExtension('User\getAll', 'order');
		if($extends) {
			$front = \Core\Base\Front::getInstance();
			foreach($extends AS $extend) {
				$objectName = $front->formatHelperName($extend->extend);
				try {
					if(class_exists($objectName)) {
						$object = new $objectName($sql, $extend, $this);
						if($object instanceof \User\UserOrderAbstract) {
							$sql = $object->getExtendetSql();
						}
					}
				} catch (\Core\Exception $e) {}
			}
		}
		
		$rows = $db->fetchRow($sql);

		if (!$rows) {
            return null;
        }
        
        if($user_id && $user_id != $id) {
        	$followHelper = new \User\Helper\Follow($rows['id']);
        	$rows['following_user'] = $followHelper->is_follow;
        } else {
        	$rows['following_user'] = 0;
        }

        return self::toRow($rows);
	}
	
	public function countUsers($filter = null) {
		$db = \Core\Db\Init::getDefaultAdapter();
		
		$rows = array('COUNT(DISTINCT user.id) AS total');
		$self = \User\User::getUserData();
		
		$sql = $db->select()
					->from('user', $rows)
					->where('user.status = 1')
					->limit(1);
				if($filter) {
					$sql->where($filter);
				}
				
		//extend order and get to show label's
		$extendTable = new \Base\Extend();
		$extends = $extendTable->getExtension('User\getAll', 'order');
		if($extends) {
			$front = \Core\Base\Front::getInstance();
			foreach($extends AS $extend) {
				$objectName = $front->formatHelperName($extend->extend);
				try {
					if(class_exists($objectName)) {
						$object = new $objectName($sql, $extend, $this);
						if($object instanceof \User\UserOrderAbstract) {
							$sql = $object->getExtendetSql();
						}
					}
				} catch (\Core\Exception $e) {}
			}
		}
		
		return $db->fetchOne($sql);
	}

	public static function exists($id)
	{
		return 0 < (new self)->countBy([ 'id' => (int) $id ]);
	}
	
	/**
	 * @param number $pin_id
	 * @return multitype:unknown multitype:NULL  |NULL
	 */
	public static function getInfo($user_id) {
		$self = new self();
		$user = $self->fetchRow(array('id = ?' => $user_id));
		if($user) {
			return array(
				'id' => $user_id,
				'stats' => array(
					'pins' => $user->pins,
					'wishlists' => $user->wishlists,
					'likes' => $user->likes,
					'followers' => $user->followers,
					'following' => $user->following,
					'comments' => $user->comments		
				)		
			);
		}
		return null;
	}
	
	public function updateInfo ($user_id) {
		$pinTable = new \Pin\Pin();
		$repinTable = new \Pin\PinRepin();
		$pinLikeTable = new \Pin\PinLike();
		$wishlistTable = new \Wishlist\Wishlist();

		return $this->update(array(
			'wishlists' => $wishlistTable->countByUserId_Status($user_id, 1),
    		'pins' => $pinTable->countByUserId_Status($user_id, 1),
    		'likes' => $pinLikeTable->countByUserId_Status($user_id, 1),
			'repins' => $repinTable->countByUserId($user_id),
    		'following' => new \Core\Db\Expr('( (SELECT COUNT(DISTINCT follow_id) FROM user_follow WHERE user_id = user.id AND follow_id != user.id AND status = 1 LIMIT 1) + (SELECT COUNT(DISTINCT follow_id) FROM wishlist_follow WHERE user_id = user.id AND follow_id != user.id AND status = 1 LIMIT 1) )'),
    		'followers' => new \Core\Db\Expr('( (SELECT COUNT(DISTINCT user_id) FROM user_follow WHERE follow_id = user.id AND user_id != user.id AND status = 1 LIMIT 1) + (SELECT COUNT(DISTINCT user_id) FROM wishlist_follow WHERE follow_id = user.id AND user_id != user.id AND status = 1 LIMIT 1) )')
		), array('id = ?' => $user_id));
	}
	
	public static function generatePassword ($length = 8) {

	    // start with a blank password
	    $password = "";
	
	    // define possible characters - any character in this string can be
	    // picked for use in the password, so if you want to put vowels back in
	    // or add special characters such as exclamation marks, this is where
	    // you should do it
	    $possible = "2346789bcdfghjkmnpqrtvwxyzBCDFGHJKLMNPQRTVWXYZ";
	
	    // we refer to the length of $possible a few times, so let's grab it now
	    $maxlength = strlen($possible);
	  
	    // check for length overflow and truncate if necessary
	    if ($length > $maxlength) {
			$length = $maxlength;
	    }
		
	    // set up a counter for how many characters are in the password so far
	    $i = 0; 
	    
	    // add random characters to $password until $length is reached
	    while ($i < $length) { 
	
			// pick a random character from the possible ones
			$char = substr($possible, mt_rand(0, $maxlength-1), 1);
	        
			// have we already used this character in $password?
			if (!strstr($password, $char)) { 
		        // no, so it's OK to add it onto the end of whatever we've already got...
		        $password .= $char;
		        // ... and increase the counter by one
		        $i++;
			}
		}
	    // done!
	    return $password;
	}
	
	public function extendDelete($user_id, $callback = null) {
		$pinTable = new \Pin\Pin();
		$pinDeleteTable = new \Base\ImageDelete();
		$sql = $pinTable->select()
						->from($pinTable,array('store','image','group' => new \Core\Db\Expr('"pinThumbs"')))
						->where('user_id = ?', $user_id);

		$sql2 = $this->select()
						->from($this,array('image'=>'avatar','store'=>'avatar_store','group' => new \Core\Db\Expr('"userAvatars"')))
						->where('id = ?', $user_id);
		$sql3 = $this->select()
						->from($this,array('image'=>'cover','store'=>'cover_store','group' => new \Core\Db\Expr('"userCovers"')))
						->where('id = ?', $user_id);
		$this->getAdapter()->beginTransaction();
		try {
			$pinDeleteTable->insertSelect($sql);
			$pinDeleteTable->insertSelect($sql2);
			$pinDeleteTable->insertSelect($sql3);
			$pinTable->extendGalleryDelete($user_id, 'user_id');
			$pinTable->delete(array('user_id = ?' => $user_id));
			$user = $this->fetchRow(array('id = ?' => $user_id));
			$rows = $user->delete();
			if($callback && is_callable($callback)) {
				call_user_func_array($callback, array($this, $rows, $user_id));
			}
			
			//stat
			$pinLikeTable = new \Pin\PinLike();
			$this->update(array(
					'likes' => new \Core\Db\Expr('('.$pinLikeTable->select()->from($pinLikeTable,'count(1)')->where('pin_like.user_id = user.id AND pin_like.status = 1').')')
			), array('status = ?' => 1));
			//stat
			
			$this->getAdapter()->commit();
			\Base\Event::trigger('user.delete',$user_id);
			return $rows;
		} catch (\Core\Db\Exception $e) {
			$this->getAdapter()->rollBack();
			throw new \Core\Exception($e->getMessage());
		}
	}
	
    public function getReserved() {
    	if($this->reserved)
    		return $this->reserved;
    	$alias_dir = \Core\Base\Action::getInstance()->getComponent('Alias')->get(__NAMESPACE__) . DS . 'controllers' . DS . '*Controller.php';
    	$files = glob($alias_dir);
    	if($files)
    		$this->reserved = array_map(function($file) {
    			return \Core\Camel::fromCamelCase(basename($file, 'Controller.php'));
    		}, $files);
    	return $this->reserved;
    }
    
    public function autocomplete($query) {
    	if(!trim($query))
    		return [];
    	$adapter = $this->getAdapter();
    	$sql = $this->select()
    				->where('username LIKE ?', new \Core\Db\Expr($adapter->quote($query . '%')))
    				->orWhere('firstname LIKE ?', new \Core\Db\Expr($adapter->quote($query . '%')))
    				->orWhere('lastname LIKE ?', new \Core\Db\Expr($adapter->quote($query . '%')))
    				->orWhere('email LIKE ?', new \Core\Db\Expr($adapter->quote($query . '%')))
    				->limit(200);
    	$result = [];
    	foreach($this->fetchAll($sql) AS $row) {
    		$result[] = [
    			'id'	=> $row->id,
    			'name'	=> $row->getUserFullname()
    		];
    	}
    	return $result;
    }
    
    public function autocomplete_search($query) {
    	if(!trim($query))
    		return null;
    	$adapter = $this->getAdapter();
    	$sql = $this->select()
    		->from($this, 'id')
	    	->where('username LIKE ?', new \Core\Db\Expr($adapter->quote('%' . $query . '%')))
	    	->where('firstname LIKE ?', new \Core\Db\Expr($adapter->quote('%' . $query . '%')))
	    	->orWhere('lastname LIKE ?', new \Core\Db\Expr($adapter->quote('%' . $query . '%')))
	    	->orWhere('email LIKE ?', new \Core\Db\Expr($adapter->quote('%' . $query . '%')));
    	
    	$username_method_show = \Base\Config::get('username_show');
    	if($username_method_show == 'fullname') {
    		$sql->orWhere('CONCAT(firstname, " ", lastname) LIKE ?', new \Core\Db\Expr($adapter->quote('%' . $query . '%')));
    	} else if($username_method_show == 'fullname-desc') {
    		$sql->orWhere('CONCAT(lastname, " ", firstname) LIKE ?', new \Core\Db\Expr($adapter->quote('%' . $query . '%')));
    	} else if($username_method_show == 'firstname') {
    		$sql->orWhere('firstname LIKE ?', new \Core\Db\Expr($adapter->quote('%' . $query . '%')));
    	} else {
    		$sql->orWhere('username LIKE ?', new \Core\Db\Expr($adapter->quote('%' . $query . '%')));
    	}
    	
    	return $sql;
    }
	
}