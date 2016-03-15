<?php

namespace Source;

class Source extends \Base\Model\Reference {
	
	protected $_referenceMap    = array(
			'Pin' => array(
					'columns'           => 'id',
					'refTableClass'     => 'Pin\Pin',
					'refColumns'        => 'source_id'
			),
	);
	
	/**
	 * @param string $source
	 * @return \Core\Db\Table\Row\mixed|NULL
	 */
	public function getSourceIdByLink($source) {
		$source = \Core\Form\Validator::clearHost($source);
		$source = preg_replace('~^www.~','',$source);
		if($source) {
			$sourceRow = $this->fetchRow(array('name = ?' => $source));
			if(!$sourceRow) {
				$sourceRow = $this->fetchNew();
				$sourceRow->name = $source;
				$sourceRow->save();
			}
			return $sourceRow->id;
		}
		return null;
	}
	
	public function extendDelete($source_id, $callback = null) {
		set_time_limit(0);
		$pinTable = new \Pin\Pin();
		$this->getAdapter()->beginTransaction();
		try {
			$pins = $pinTable->fetchAll(['source_id = ?' => $source_id]);
			foreach($pins AS $pin) {
				$pinTable->extendDelete($pin->id, $callback);
			}
			
			$store = $this->fetchRow(array('id = ?' => $source_id));
			$rows = $store->delete();
			if($callback && is_callable($callback)) {
				call_user_func_array($callback, array($this, $rows, $source_id));
			}
				
			$this->getAdapter()->commit();
			return $rows;
		} catch (\Core\Db\Exception $e) {
			$this->getAdapter()->rollBack();
			throw new \Core\Exception($e->getMessage());
		}
	}
    
    public function autocomplete($query) {
    	if(!trim($query))
    		return [];
    	$adapter = $this->getAdapter();
    	$sql = $this->select()
    				->where('name LIKE ?', new \Core\Db\Expr($adapter->quote($query . '%')))
    				->limit(200);
    	$result = [];
    	foreach($this->fetchAll($sql) AS $row) {
    		$result[] = [
    			'id'	=> $row->id,
    			'name'	=> $row->name
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
	    	->where('name LIKE ?', new \Core\Db\Expr($adapter->quote('%' . $query . '%')));
    	
    	return $sql;
    }
	
}