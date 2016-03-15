<?php

namespace Core\Db;

class Hierarchical extends \Core\Db\Table {

	protected $_left;
	protected $_right;
	protected $_pk;
	protected $_node = 'node';
	protected $_parent = 'parent';
	
	const LEFT = 'left';
	const RIGHT = 'right';
	const PK = 'pk';
	const NODETABLD = 'node';
	const PARENTTABLD = 'parent';

	/**
	 * __construct() - For concrete implementation of Core\Db\Table
	 *
	 * @param string|array $config
	 *        	string can reference a \Core\Registry key for a db adapter
	 *        	OR it can reference the name of a table
	 * @param array|\Core\Db\Table\Definition $definition
	 *
	 * @return \Core\Db\Table\AbstractTable
	 */
	public function __construct($config = array(), $definition = null) {
		parent::__construct ( $config, $definition );
		if(!$this->_pk) { $this->_pk = array_shift($this->info('primary')); }
		$this->_setupLtfRgh();
	}
	
	/**
	 * setOptions()
	 *
	 * @param array $options
	 * @return \Core\Db\Table\AbstractTable
	 */
	public function setOptions(Array $options) {
		parent::setOptions($options);
		foreach ( $options as $key => $value ) {
			switch ($key) {
				case self::LEFT :
					$this->_left = $value;
					break;
				case self::RIGHT :
					$this->_right = $value;
					break;
				case self::PK :
					$this->_pk = $value;
					break;
				case self::NODETABLD :
					$this->_node = $value;
					break;
				case self::PARENTTABLD :
					$this->_parent = $value;
					break;
				default :
					// ignore unrecognized configuration directive
					break;
			}
		}
		return $this;
	}
	
	protected function _setupLtfRgh() {
		if(!$this->_left || !$this->_right || !$this->_pk) {
			throw new \Core\Db\Table\Exception('Parameters "left","right" and "pk" is required');
		}
	}
	
	/**
	 * Gets an object with all data of a node
	 * @param integer $id id of the node
	 * @return object object with node-data (id, lft, rgt)
	 */
	protected function getNode($id) {
		$select = $this->select()
						->where($this->_pk . ' = ?', $id)
						->limit(1);
		return $this->fetchRow($select);
	}
	
	/**
	 * Creates the root node
	 * @param string $name Name of the new node
	 * @return boolean true
	 */
	public function createRootNode($data = array()) {
		$select = $this->select()->from($this, array('maxright' => new \Core\Db\Expr('MAX(' . $this->getAdapter()->quoteIdentifier($this->_right) . ')')));
		$result = $this->fetchRow($select);
		if($result && $result->maxright) {
			$data[$this->_left] = ($result->maxright + 1);
			$data[$this->_right] = ($result->maxright + 2);
		} else {
			$data[$this->_left] = 1;
			$data[$this->_right] = 2;
		}
		
		return $this->insert($data);
	}
	
	/**
	 * Creates a new child node of the node with the given id
	 * @param string $name name of the new node
	 * @param integer $parent id of the parent node
	 * @return boolean true
	 */
	public function insertChildNode($parent, $data = array()) {
		$p_node = $this->getNode($parent);
		if(!$p_node) {
			return false;
		}
		return $this->insertNode($p_node->{$this->_left}, $p_node->{$this->_right}, $data);
	}
	
	/**
	 * Creates a new node
	 * @param string $name name of the new node
	 * @param integer $lft lft of parent node
	 * @param integer $rgt	rgt of parent node
	 * @return boolean	true
	 */
	protected function insertNode($lft, $rgt, $data) {
		$this->update(array(
			$this->_right => new \Core\Db\Expr($this->getAdapter()->quoteIdentifier($this->_right) . ' + 2')
		), array($this->getAdapter()->quoteIdentifier($this->_right) . ' >= ?' => $rgt));
		$this->update(array(
			$this->_left => new \Core\Db\Expr($this->getAdapter()->quoteIdentifier($this->_left) . ' + 2')
		), array($this->getAdapter()->quoteIdentifier($this->_left) . ' > ?' => $rgt));
		
		$data[$this->_left] = $rgt;
		$data[$this->_right] = ($rgt + 1);
		
		return $this->insert($data);
	}

	/**
	 * Creates a multi-dimensional array of the whole tree
	 * @return array multi-dimenssional array of the whole tree
	 */
	public function getTree($id = null) {
		$_left = $this->getAdapter()->quoteIdentifier($this->_left);
		$_right = $this->getAdapter()->quoteIdentifier($this->_right);
		$_node = $this->getAdapter()->quoteIdentifier($this->_node);
		$_parent = $this->getAdapter()->quoteIdentifier($this->_parent);
		$select = $this->select()
					->from(array($this->_node => $this->_name))
					->columns(array('level' => 'COUNT(*)'))
					->join(array($this->_parent => $this->_name),"{$_node}.{$_left} BETWEEN {$_parent}.{$_left} AND {$_parent}.{$_right}", '')
					->group($this->_node . '.' . $this->_left)
					->order($this->_node . '.' . $this->_left . ' ASC')
					/*->where($condition ? $condition : ' 1 ')*/;
		if($id) {
			$node = $this->getNode($id);
			if(!$node) {
				return $this->fetchAll(array($this->_pk => 0),null,0,0);
			}
			$select->where("{$_node}.{$_left} BETWEEN " . (int)$node->{$this->_left} . " AND " . (int)$node->{$this->_right} . "")
					->where($this->_node . '.' . $this->_pk . ' != ?',$id)
					->columns(array('level' => 'COUNT(*)-1'));
		}
		
		return $this->fetchAll($select);
	}

	/**
	 * Creates a multi-dimensional array of the whole tree
	 * @return array multi-dimenssional array of the whole tree
	 */
	public function getChildNodes($id) {
		$_left = $this->getAdapter()->quoteIdentifier($this->_left);
		$_right = $this->getAdapter()->quoteIdentifier($this->_right);
		$_node = $this->getAdapter()->quoteIdentifier($this->_node);
		$_parent = $this->getAdapter()->quoteIdentifier($this->_parent);
		$node = $this->getNode($id);
		if(!$node) {
			return $this->fetchAll(array($this->_pk . ' = ?' => 0),null,0,0);
		}
		$select = $this->select()
					->from(array($this->_node => $this->_name))
					->join(array($this->_parent => $this->_name),"{$_node}.{$_left} BETWEEN {$_parent}.{$_left} AND {$_parent}.{$_right}", '')
					->group($this->_node . '.' . $this->_left)
					->order($this->_node . '.' . $this->_left . ' ASC')
					->where("{$_node}.{$_left} BETWEEN " . (int)$node->{$this->_left} . " AND " . (int)$node->{$this->_right} . "")
					->where($this->_node . '.' . $this->_pk . ' != ?',$id)
					->columns(array('level' => 'COUNT(*)'));
			
		
		return $this->fetchAll($select);
	}

	/**
	 * Deletes a node an all it's children
	 * @param integer $id id of the node to delete
	 * @return boolean true
	 */
	public function deleteNode($id) {
		$node = $this->getNode($id);
		if(!$node) {
			return false;
		}
		$_left = $this->getAdapter()->quoteIdentifier($this->_left);
		$_right = $this->getAdapter()->quoteIdentifier($this->_right);
		$delete = $this->delete(new \Core\Db\Expr($_left . ' BETWEEN ' . (int)$node->{$this->_left} . ' AND ' . (int)$node->{$this->_right}));
		$this->update(array(
			$this->_left => new \Core\Db\Expr($_left . ' - ROUND(' . (int)$node->{$this->_right} . ' + ' . (int)$node->{$this->_left} . ' + 1)')
		), array($_left . ' > ?' => (int)$node->{$this->_right}));
		$this->update(array(
			$this->_right => new \Core\Db\Expr($_right . ' - ROUND(' . (int)$node->{$this->_right} . ' + ' . (int)$node->{$this->_left} . ' + 1)')
		), array($_right . ' > ?' => (int)$node->{$this->_right}));
		return $delete;
	}
	
	/**
	 * Deletes a node and increases the level of all children by one
	 * @param integer $id id of the node to delete
	 * @return boolean true
	 */
	public function deleteSingleNode($id) {
		$node = $this->getNode($id);
		if(!$node) {
			return false;
		}
		$_left = $this->getAdapter()->quoteIdentifier($this->_left);
		$_right = $this->getAdapter()->quoteIdentifier($this->_right);
		$delete = $this->delete(array($_left . ' = ?' => $node->{$this->_left}));
		$this->update(array(
			$this->_left => new \Core\Db\Expr($_left . ' - 1'),
			$this->_right => new \Core\Db\Expr($_right . ' - 1')		
		), new \Core\Db\Expr($_left . ' BETWEEN ' . (int)$node->{$this->_left} . ' AND ' . (int)$node->{$this->_right}));
		$this->update(array(
				$this->_left => new \Core\Db\Expr($_left . ' - 2')
		), new \Core\Db\Expr($_left . ' > ' . (int)$node->{$this->_right}));
		$this->update(array(
				$this->_right => new \Core\Db\Expr($_right . ' - 2')
		), new \Core\Db\Expr($_right . ' > ' . (int)$node->{$this->_right}));
		return $delete;
	}

	/**
	 * Gets a multidimensional array containing the path to defined node
	 * @param integer $id id of the node to which the path should point
	 * @return array multidimensional array with the data of the nodes in the tree
	 */
	public function getPath($id) {
		$_left = $this->getAdapter()->quoteIdentifier($this->_left);
		$_right = $this->getAdapter()->quoteIdentifier($this->_right);
		$_node = $this->getAdapter()->quoteIdentifier($this->_node);
		$_parent = $this->getAdapter()->quoteIdentifier($this->_parent);
		$_pk = $this->getAdapter()->quoteIdentifier($this->_pk);
		$select = $this->select()
			->from(array($this->_node => $this->_name), '')
			->join(array($this->_parent => $this->_name),"{$_node}.{$_left} BETWEEN {$_parent}.{$_left} AND {$_parent}.{$_right}")
			->order($this->_node . '.' . $this->_left . ' ASC')
			->where($this->_node . '.' . $_pk . ' = ?', $id);
		
		return $this->fetchAll($select);
	}

	/**
	 * Gets the id of a node depending on it's rgt value
	 * @param integer $rgt rgt value of the node
	 * @return integer id of the node
	 */
	protected function getIdRgt($rgt) {
		$_right = $this->getAdapter()->quoteIdentifier($this->_right);
		$_pk = $this->getAdapter()->quoteIdentifier($this->_pk);
		$select = $this->select()
						->from($this,$_pk)
						->where($_right . ' = ?', $rgt);
		$result = $this->fetchRow($select);
		if(!$result) {
			return false;
		}
		return $result->{$this->_pk};
	}

	/**
	 * Moves a node one position to the left staying in the same level
	 * @param $nodeId id of the node to move
	 * @return boolean true
	 */
	public function moveLft($nodeId) {
		$node = $this->getNode($nodeId);
		if(!$node) {
			return false;
		}
		$_left = $this->getAdapter()->quoteIdentifier($this->_left);
		$_right = $this->getAdapter()->quoteIdentifier($this->_right);
		$_node = $this->getAdapter()->quoteIdentifier($this->_node);
		$_parent = $this->getAdapter()->quoteIdentifier($this->_parent);
		$_pk = $this->getAdapter()->quoteIdentifier($this->_pk);
		$brotherId = $this->getIdRgt($node->{$this->_left}-1);
		if ($brotherId == false) {
			return false;
		}
		$brother = $this->getNode($brotherId);
		if(!$brother) {
			return false;
		}
	
		$nodeSize = $node->{$this->_right} - $node->{$this->_left} + 1;
		$brotherSize = $brother->{$this->_right} - $brother->{$this->_left} + 1;
		$select = $this->select()
							->from($this,$_pk)
							->where(new \Core\Db\Expr($_left . ' BETWEEN ' . (int)$node->{$this->_left} . ' AND ' . (int)$node->{$this->_right}));
		$result = $this->fetchAll($select);
		$idsNotToMove = array();
		foreach ($result AS $obj) {
			$idsNotToMove[] = $obj->{$this->_pk};
		}
		
		$t = $this->update(array(
			$this->_left => new \Core\Db\Expr($_left . ' - ' . $brotherSize),
			$this->_right => new \Core\Db\Expr($_right . ' - ' . $brotherSize)
		), new \Core\Db\Expr($_left . ' BETWEEN ' . (int)$node->{$this->_left} . ' AND ' . (int)$node->{$this->_right}));
		
		$r = $this->update(array(
				$this->_left => new \Core\Db\Expr($_left . ' + ' . $nodeSize),
				$this->_right => new \Core\Db\Expr($_right . ' + ' . $nodeSize)
		), new \Core\Db\Expr($_left . ' BETWEEN ' . (int)$brother->{$this->_left} . ' AND ' . (int)$brother->{$this->_right} . ($idsNotToMove ? (' AND ' . $_pk . ' NOT IN (' . implode(',', $idsNotToMove) . ')') : '')));
		return $t + $r;
	}
	
	/**
	 * Gets the id of a node depending on it's lft value
	 * @param integer $lft lft value of the node
	 * @return integer id of the node
	 */
	protected function getIdLft($lft) {
		$_left = $this->getAdapter()->quoteIdentifier($this->_left);
		$_pk = $this->getAdapter()->quoteIdentifier($this->_pk);
		$select = $this->select()
			->from($this,$_pk)
			->where($_left . ' = ?', $lft);
		$result = $this->fetchRow($select);
		if(!$result) {
			return false;
		}
		return $result->{$this->_pk};
	}
	
	
	/**
	 * Moves a node one position to the right staying in the same level
	 * @param $nodeId id of the node to move
	 * @return boolean true
	 */
	public function moveRgt($nodeId) {
		$node = $this->getNode($nodeId);
		if(!$node) {
			return false;
		}
		$_left = $this->getAdapter()->quoteIdentifier($this->_left);
		$_right = $this->getAdapter()->quoteIdentifier($this->_right);
		$_node = $this->getAdapter()->quoteIdentifier($this->_node);
		$_parent = $this->getAdapter()->quoteIdentifier($this->_parent);
		$_pk = $this->getAdapter()->quoteIdentifier($this->_pk);
		$brotherId = $this->getIdLft($node->{$this->_right}+1);
		if ($brotherId == false) {
			return false;
		}
		$brother = $this->getNode($brotherId);
		if(!$brother) {
			return false;
		}
	
		$nodeSize = $node->{$this->_right} - $node->{$this->_left} + 1;
		$brotherSize = $brother->{$this->_right} - $brother->{$this->_left} + 1;
	
		$select = $this->select()
					->from($this,$_pk)
					->where(new \Core\Db\Expr($_left . ' BETWEEN ' . (int)$node->{$this->_left} . ' AND ' . (int)$node->{$this->_right}));
		$result = $this->fetchAll($select);
		$idsNotToMove = array();
		foreach ($result AS $obj) {
			$idsNotToMove[] = $obj->{$this->_pk};
		}
		
		$t = $this->update(array(
				$this->_left => new \Core\Db\Expr($_left . ' + ' . $brotherSize),
				$this->_right => new \Core\Db\Expr($_right . ' + ' . $brotherSize)
		), new \Core\Db\Expr($_left . ' BETWEEN ' . (int)$node->{$this->_left} . ' AND ' . (int)$node->{$this->_right}));
		
		$r = $this->update(array(
				$this->_left => new \Core\Db\Expr($_left . ' - ' . $nodeSize),
				$this->_right => new \Core\Db\Expr($_right . ' - ' . $nodeSize)
		), new \Core\Db\Expr($_left . ' BETWEEN ' . (int)$brother->{$this->_left} . ' AND ' . (int)$brother->{$this->_right} . ($idsNotToMove ? (' AND ' . $_pk . ' NOT IN (' . implode(',', $idsNotToMove) . ')') : '')));
		return $t + $r;
	}
	
	/**
	 * Get the HTML code for an unordered list of the tree
	 * @return string HTML code for an unordered list of the whole tree
	 */
	public function treeAsHtml() {
		$tree = $this->getTree();
		$html = "<ul>\n";
		for ($i=0; $i<count($tree); $i++) {
			$html .= "<li>" . $tree[$i][$this->name];
			if ($tree[$i]['level'] < $tree[$i+1]['level']) {
				$html .= "\n<ul>\n";
			} elseif ($tree[$i]['level'] == $tree[$i+1]['level']) {
				$html .= "</li>\n";
			} else {
				$diff = $tree[$i]['level'] - $tree[$i+1]['level'];
				$html .= str_repeat("</li>\n</ul>\n", $diff) . "</li>\n";
			}
		}
		$html .= "</ul>\n";
		return $html;
	}

	
}