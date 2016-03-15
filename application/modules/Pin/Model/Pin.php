<?php
namespace Pin;

use \Core\Base\MemcachedManager;

use \Core\Db\Init;
use \Core\Db\Table\Row\DynamicRow;

class Pin extends \Base\Model\Reference {
	
	public function __construct($config = array()) {
		parent::__construct($config);
		$this->setRowClass('\Pin\PinRow');
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
			'Source' => array(
					'columns'           => 'source_id',
					'refTableClass'     => 'Source\Source',
					'refColumns'        => 'id'
			),
			'User' => array(
					'columns'           => 'user_id',
					'refTableClass'     => 'User\User',
					'refColumns'        => 'id'
			),
			'Like' => array(
					'columns'           => 'pin_id',
					'refTableClass'     => 'Pin\PinLike',
					'refColumns'        => 'id',
					'where'				=> '"user_id = " . \User\User::getUserData()->id'
			),
			'Repin' => array(
					'columns'           => 'id',
					'refTableClass'     => 'Pin\PinRepin',
					'refColumns'        => 'pin_id'
			),
	);
	
	/**
	 * Generate md5 form key
	 * @return string
	 */
	public static function getXFormCmd($key = null) {
		$request = \Core\Http\Request::getInstance();
		return md5(
				$key
				. $request->getServer('HTTP_USER_AGENT')
		) . '-' . substr(md5(
				$request->getServer('SERVER_ADDR')
		), 5, 10);
		/*return md5(
			$key
			. $request->getClientIp()
			. $request->getServer('HTTP_USER_AGENT')
			. \Core\Session\Base::sid()
			. $request->getServer('SERVER_ADDR')
		) . '-' . substr(md5(
			\Core\Session\Base::sid()
			. $request->getServer('SERVER_ADDR')
		), 0, 10);*/
	}

	/**
	* @param string $table
	* @return array 
	*/
	public static function describeTable($table)
	{
		$db = \Core\Db\Init::getDefaultAdapter();
		$data = $db->describeTable($table);
		
		$result = [];
		
		foreach($data AS $res)
			$result[$res['COLUMN_NAME']] = $res['COLUMN_NAME'];
		
		return $result;
	}
	
	/*
	 * @return \Core\Db\Select 
	 */
	public function queryBuilder($filter = null, $order = 'pin.id DESC', $limit = 25, $offset = null, $use_index = null, $repins = FALSE) {
		$db = \Core\Db\Init::getDefaultAdapter();
		$self = \User\User::getUserData();

		$row_1 = $row_2 = [
			'liked' => new \Core\Db\Expr(0),
			'user_is_follow' => new \Core\Db\Expr(0),
            'in_wishlist' => new \Core\Db\Expr(0),
            'redirect_from' => 'pin.from',
		];
		$desc = self::describeTable('pin');

		$row_1['pin_selection'] = new \Core\Db\Expr('"original"');
		$row_2['pin_selection'] = new \Core\Db\Expr('"repin"');

		foreach($desc AS $col => $def)
		{
			$row_1[] = $col;
			$key = in_array($col, [ /*'user_id',*/'wishlist_id' ]) ? "`pin_repin`.`$col` AS $col" : "`pin`.`$col`";
			$row_2[] = new \Core\Db\Expr( $key );
		}

		$row_1[] = new \Core\Db\Expr('`pin`.`date_added` AS `pin_order`, `pin`.`from` AS `redirect_from`');
		$row_2[] = new \Core\Db\Expr('`pin_repin`.`date_added` AS `pin_order`, `pin`.`from` AS `redirect_from`');
		
		if($repins) {
			$sql = $db->select()->from('pin_repin', $row_2)
					->joinLeft('pin', 'pin_repin.pin_id = pin.id', '')
					->joinLeft('wishlist', 'pin.wishlist_id=wishlist.id', 'title AS wishlist');
		} else {
			$sql = $db->select()->from('pin', $row_1);
		}

        //added in version 4.4.0
		if(static::compare()) {
			$sql->where('IF(pin.product = 1, IF(pin.user_id = ' . (int)$self->id . ', 1, pin.active), 1) = 1');
			if($self->id) {
				if (!$self->is_admin) {
					$sql->where('IF(pin.product = 1, IF(pin.user_id = ?, 1, pin.active), 1) = 1', $self->id);
				}
			} else {
				$sql->where('IF(pin.product = 1, pin.active, 1) = 1');
			}
		}
        //added in version 4.4.0

		$sql->joinLeft('user', 'pin.user_id=user.id',[ 'username','firstname', 'lastname','pins','wishlists','user_likes' => 'likes','followers','avatar_width', 'avatar_height', 'avatar', 'avatar_store_host', 'avatar_store','email'])
				// 			->where('wishlist.status = 1')
				->where('IF(pin.user_id = ' . (int)$self->id . ',1, pin.public) = 1')
				->where('user.status = 1')
				->order($order ?: 'pin_order DESC')
				//->useIndex('PRIMARY')
				->limit($limit, $offset);
		
		if($filter)
			$sql->where($filter);

		
		$sql->where('pin.status = 1');
		
		if(($id = \User\User::getUserData()->id) > 0) {
			$sql->columns(array('liked'=> new \Core\Db\Expr('('.$db->select()->from('pin_like','COUNT(id)')->where('user_id = ?',$id)->where('pin.id = pin_id')->limit(1).')')));
			$sql->columns(array('user_is_follow'=> new \Core\Db\Expr('(('.$db->select()->from('user_follow', 'COUNT(DISTINCT follow_id)')->where('user_id = ?', $id)->where('follow_id = pin.user_id')->limit(1).')+('.$db->select()->from('wishlist_follow','COUNT(DISTINCT follow_id)')->where('user_id = ?', $id)->where('follow_id = pin.user_id')->limit(1) .') - ('.$db->select()->from('wishlist_follow_ignore','COUNT(DISTINCT follow_id)')->where('user_id = ?', $id)->where('follow_id = pin.user_id')->limit(1) .'))')));
			$sql->columns(['in_wishlist' => new \Core\Db\Expr('('.$db->select()->from('pin_repin','COUNT(id)')->where('user_id = ?',$id)->where('pin.id = pin_id')->limit(1).')')]);
		} 
				
		//extend order and get to show label's
		$extendTable = new \Base\Extend();
		$extends = $extendTable->getExtension('Pin\getAll', 'order');
		if($extends) {
			$front = \Core\Base\Front::getInstance();
			foreach($extends AS $extend) {
				$objectName = $front->formatHelperName($extend->extend);
				try {
					if(class_exists($objectName)) {
						$object = new $objectName($sql, $extend, $this);
						if($object instanceof \Pin\PinOrderAbstract) {
							$sql = $object->getExtendetSql();
						}
					}
				} catch (\Core\Exception $e) {}
			}
		}
		
		return $sql;
	}
	
	public function getAll($filter = null, $order = 'pin.id DESC', $limit = 25, $offset = null, $use_index = null, $repins = FALSE) {
		
		$sql = $this->queryBuilder($filter, $order, $limit, $offset, $use_index, $repins);
		
		$db = \Core\Db\Init::getDefaultAdapter();
		$self = \User\User::getUserData();

		$rows = $db->fetchAll($sql);
		//extend order and get to show label's
		$extendTable = new \Base\Extend();
		$extends = $extendTable->getExtension('Pin\getAll', 'order');
		if($extends) {
			$front = \Core\Base\Front::getInstance();
			foreach($extends AS $extend) {
				$objectName = $front->formatHelperName($extend->extend);
				try {
					if(class_exists($objectName)) {
						$object = new $objectName($sql, $extend, $this);
						if($object instanceof \Pin\PinOrderAbstract && method_exists($object, 'rowsExtended')) {
							$extendRows = $object->rowsExtended($rows);
							if($extendRows) {
								$rows = $extendRows;
							}
						}
					}
				} catch (\Core\Exception $e) {}
			}
		}

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
	
	public function getAllToRow($sql) {
		
		$db = \Core\Db\Init::getDefaultAdapter();

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
	
	public function getCount($filter = null, $repins = FALSE) {
		
		$sql = $this->queryBuilder($filter, null, null, null, null, $repins)
				->reset('columns')
				->reset(\Core\Db\Select::ORDER)
				->columns(['total' => 'COUNT(pin.id)'])
				->limit(1);

		return $this->getAdapter()->fetchOne($sql);
	}
	
	/**
	 * @param number $id
	 * @return multitype:
	 */
	public function get($id, $current = true, $anticache = false) { 
		static $pins = [];
		$db = \Core\Db\Init::getDefaultAdapter();

		if($current === 'next') {
			$sql = $this->queryBuilder('pin.id > ' . $db->quote((string)$id), 'pin.id ASC', 1)->forceIndex('PRIMARY')->reset(\Core\Db\Select::ORDER)->order('pin.id ASC');
		} elseif($current === 'prev') {
			$sql = $this->queryBuilder('pin.id < ' . $db->quote((string)$id), 'pin.id DESC', 1)->forceIndex('PRIMARY')->reset(\Core\Db\Select::ORDER)->order('pin.id DESC');
		} else {
			$sql = $this->queryBuilder('pin.id = ' . $db->quote((string)$id), 'pin.id DESC', 1)->forceIndex('PRIMARY');
		}

		$key = md5((string)$sql . $current);
		if(isset($pins[$key])) {
			return $pins[$key];
		}
		if($anticache) {
			$rows = $db->fetchRow($sql);
		} else {
			$cache_key = MemcachedManager::key(__CLASS__, __METHOD__, \User\User::getUserData()->id, $id, $current, $sql);

			$rows = MemcachedManager::get($cache_key, function() use($db, $sql) {
				return $db->fetchRow($sql);
			});
		}

		if (!$rows) {
			return ($pins[$key] = null);
		}

        $extends = (new \Base\Extend())->getExtension('Pin\getAll', 'order');
		
		if(TRUE === $current && $extends && $extends->count()) {
			$front = \Core\Base\Front::getInstance();
			foreach($extends AS $extend) {
				$objectName = $front->formatHelperName($extend->extend);
				try {
					if(class_exists($objectName)) {
						$object = new $objectName($sql, $extend, $this);
						if($object instanceof \Pin\PinOrderAbstract && method_exists($object, 'rowsExtended')) {
							$extendRows = $object->rowsExtended($rows);
							if($extendRows) {
								$rows = $extendRows;
							}
						}
					}
				} catch (\Core\Exception $e) {}
			}
		}

		$data = [
			'table'     => $this,
			'data'      => $rows,
			'readOnly'  => true,
			'stored'    => true
		];

		$rowClass = $this->getRowClass();
		if (!class_exists($rowClass)) {
			require_once 'Loader.php';
			\Core\Loader\Loader::loadClass($rowClass);
		}
		
		return ($pins[$key] = new $rowClass($data));
	}

	public function pinToRow($rows) {
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

	public function pinToRowset($rows) {
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
	
	/**
	 * @param number $limit
	 * @return Ambigous <multitype:, multitype:unknown >
	 */
	public static function getSitemap($limit = 1000000) {
		$db = \Core\Db\Init::getDefaultAdapter();
		$sql = $db->select()
					->from('pin',array('id','title'))
					->joinLeft('wishlist', 'pin.wishlist_id=wishlist.id', '')
					->joinLeft('user', 'pin.user_id=user.id', '')
					->where('user.status = 1')
					->where('wishlist.public = 1')
					->where('wishlist.status = 1')
					->order('id DESC')
					->limit($limit);
		return $db->fetchPairs($sql);
	}
	
	/**
	 * @param number $pin_id
	 * @return multitype:unknown multitype:NULL  |NULL
	 */
	public static function getInfo($pin_id) {
		$self = new self();
		$pin = $self->fetchRow(array('id = ?' => $pin_id));
		if($pin) {
			return array(
				'id' => $pin_id,
				'stats' => array(
					'likes' => $pin->likes,
					'comments' => $pin->comments,
					'repins' => $pin->countRepins()
				)	
			);
		}
		return null;
	}
	
	public function updateInfo($pin_id) {
		$pinLikeTable = new \Pin\PinLike();
		$pinCommentsTable = new \Pin\PinComment();
		$this->update(array(
			'likes'	=> $pinLikeTable->countByPinId_Status($pin_id,1),
			'comments' => $pinCommentsTable->countByPinId($pin_id),
			'repins' => $this->countByParentId_Status($pin_id, 1),
		), array('id = ?' => $pin_id));
	}
	
	public function extendDelete($pin_id, $callback = null) {
		$sql = $this->select()
						->from($this,array('store','image','group' => new \Core\Db\Expr('"pinThumbs"')))
						->where('id = ?', $pin_id);
		
		$pinDeleteTable = new \Base\ImageDelete();
		$this->getAdapter()->beginTransaction();
		try {
			$pinDeleteTable->insertSelect($sql);
			$this->extendGalleryDelete($pin_id);
			$pin = $this->fetchRow(array('id = ?' => $pin_id));
			$pinArray = $pin->toArray();
			$rows = $pin->delete();
			if($callback && is_callable($callback)) {
				call_user_func_array($callback, array($this, $rows, $pin_id));
			}
			
			if($pinArray['source_id']) {
				$sourceTable = new \Source\Source();
				$sourceTable->update(array('pins'=>$this->countBySourceId_Status($pinArray['source_id'],1)), array('id = ?' => $pinArray['source_id']));
			}
			
			if($pinArray['user_id']) {
				$userTable = new \User\User();
				$userTable->updateInfo($pinArray['user_id']);
				$pinLikeTable = new \Pin\PinLike();
				$userTable->update(array(
						'likes' => new \Core\Db\Expr('('.$pinLikeTable->select()->from($pinLikeTable,'count(1)')->where('pin_like.user_id = user.id AND pin_like.status = 1').')')
				), array('status = ?' => 1));
			}
			
			$this->getAdapter()->commit();
			return $rows;
		} catch (\Core\Db\Exception $e) {
			$this->getAdapter()->rollBack();
			throw new \Core\Exception($e->getMessage());
		}
	}
	
	public function extendGalleryDelete($id, $key = 'id') { 
		$pinGalleryTable = new \Pin\PinGallery();
		$pinTable = new \Pin\Pin();
		$sql = $pinGalleryTable->select()
						->from($pinGalleryTable,array('store','image','group' => new \Core\Db\Expr('"pinGallery"')))
						->where('pin_id IN ?', $pinTable->select()->from($pinTable,'id')->where($key . ' = ?', $id));
		
		$pinGalleryDeleteTable = new \Base\ImageDelete();
		return $pinGalleryDeleteTable->insertSelect($sql);
	}

	public static function compare() {
		if(version_compare(\Core\Registry::get('system_version'), '4.4.0', '<'))
			return false;
		if( !(new \Pin\Pin)->hasCol('active') )
			return false;
		return true;
	}
}