<?php

namespace Tag;

class Tag extends \Base\Model\Reference {
	
	public function __construct($config = array()) {
		parent::__construct($config);
		$this->setRowClass('\Tag\TagRow');
	}

	protected $_referenceMap    = array(
			'PinTag' => array(
				'columns'           => 'id',
				'refTableClass'     => 'Tag\PinTag',
				'refColumns'        => 'tag_id',
				'singleRow'			=> true
			),
			'Letter' => array(
				'columns'           => 'letter_id',
				'refTableClass'     => 'Tag\TagLetter',
				'refColumns'        => 'id',
				'singleRow'			=> true
			),
	);
	
	public function fetchByLeterWithPinCount($leter_id) {
		$adapter = $this->getAdapter();
		
		$rows = ['*'];
		$rows['pins'] = new \Core\Db\Expr('(SELECT COUNT(p.id) FROM pin_tag pt LEFT JOIN pin p ON (p.id = pt.pin_id) WHERE pt.tag_id = tag.id AND p.status = 1)');
		$sql = $adapter->select()
					->from($this->_name, $rows)
					->where('letter_id = ?', $leter_id);
		
		$sql = $adapter->select()
					->from(['tag' => $sql])
					->where('pins > 0')
					->order('tag ASC')
					->limit(5000);
		
		$rows = $adapter->fetchAll($sql);

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
	
	public static function getPinsByTag($tag) {
		$self = new self();
		return $self->getAdapter()->select()->from('tag','')->joinLeft('pin_tag', 'tag.id=pin_tag.tag_id','pin_id')->where($self->makeWhere(array('tag'=>new \Core\Db\Expr($self->getAdapter()->quote($tag)))));
	}
	
}