<?php

namespace Interest;

class Interest extends \Base\Model\Reference {
	
	public function __construct($config = array()) {
		parent::__construct($config);
		$this->setRowClass('\Interest\Row');
	}
	
	protected $_referenceMap    = array(
		'Category' => array(
				'columns'           => 'category_id',
				'refTableClass'     => 'Category\Category',
				'refColumns'        => 'id',
				'referenceMap'		=> array(
					'columns'           => 'category_id',
					'refTableClass'     => 'Category\CategoryDescription',
					'refColumns'        => 'category_id',
					'where'				=> '"language_id = " . \Core\Base\Action::getModule(\'Language\')->getLanguageId()'
				)
		),
		'Related' => array(
			'columns'           => 'id',
			'refTableClass'     => 'Interest\InterestRelated',
			'refColumns'        => 'related_id'
		),
		'Tag' => array(
			'columns'           => 'id',
			'refTableClass'     => 'Interest\InterestTag',
			'refColumns'        => 'interest_id'
		),
		'Pin' => array(
			'columns'           => 'id',
			'refTableClass'     => 'Interest\InterestPin',
			'refColumns'        => 'interest_id'
		),
		'IsFollow' => array(
			'columns'           => 'id',
			'refTableClass'     => 'Interest\InterestFollow',
			'refColumns'        => 'interest_id',
			'where'				=> '"user_id = " . (int)\User\User::getUserData()->id'
		),
		'Follow' => array(
			'columns'           => 'id',
			'refTableClass'     => 'Interest\InterestFollow',
			'refColumns'        => 'interest_id'
		)
	);
	
	//virtual map for reference
	protected $_referenceReverseMap    = array(
		'Category\Category' => array(
			'columns'           => 'id',
			'refTableClass'     => 'Interest\Interest',
			'refColumns'        => 'category_id',
			'singleRow'			=> true
		)
	);
	
	public function getByQuery($query) {
		$db = $this->getAdapter();
		$sql = $db->select()
			->from('interest')
			->where($this->makeWhere(array(
				'query' => new \Core\Db\Expr($db->quote($query))
			)))
			->limit(1);
		
		$sql->columns(['date_modified' => '(SELECT date_added FROM interest_pin ip LEFT JOIN pin p ON (p.id = ip.pin_id) WHERE ip.interest_id = interest.id ORDER BY p.id DESC LIMIT 1)']);
			
		$row = $db->fetchRow($sql);
		if(!$row)
			return null;
		
		return $this->_toRow($row);
		
	}
	
	public function get($interest_id) {
		$db = $this->getAdapter();
		$sql = $db->select()
			->from('interest')
			->where('id = ?', $interest_id)
			->limit(1);
		
		$sql->columns(['date_modified' => '(SELECT date_added FROM interest_pin ip LEFT JOIN pin p ON (p.id = ip.pin_id) WHERE ip.interest_id = interest.id ORDER BY p.id DESC LIMIT 1)']);
			
		$row = $db->fetchRow($sql);
		if(!$row)
			return null;
		
		return $this->_toRow($row);
		
	}
	
	public function getRelated($interest_id, $limit = 200) {
		
		$interest = $this->get($interest_id);
		if(!$interest)
			return;
		
		$db = $this->getAdapter();
		$sql = $db->select()
					->from('interest_related', '')
					->joinLeft('interest', 'interest_related.related_id = interest.id')
					->where('interest_related.interest_id = ?', $interest_id)
					->where('interest.category_id = ?', $interest->category_id)
					->limit($limit);
		
		$sql->columns(['date_modified' => '(SELECT date_added FROM interest_pin ip LEFT JOIN pin p ON (p.id = ip.pin_id) WHERE ip.interest_id = interest.id ORDER BY p.id DESC LIMIT 1)']);
		$sql->order('date_modified DESC');
		
		return $this->_toRowset($db->fetchAll($sql));
					
	}
	
	private function _toRow($rows) {
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
	
	private function _toRowset($rows) {
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
	
}