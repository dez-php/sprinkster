<?php

namespace Interest;

class Row extends \Core\Db\Table\Row {
	
    /**
     * Allows pre-insert logic to be applied to row.
     * Subclasses may override this method.
     *
     * @return void
     */
    protected function _insert()
    {
    	$this->renameIfExist();
    }

    /**
     * Allows pre-update logic to be applied to row.
     * Subclasses may override this method.
     *
     * @return void
     */
    protected function _update()
    {
    	$this->renameIfExist();
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
    	\Base\Event::trigger('interest.insert',$this->id);
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
    	//register events
    	\Base\Event::trigger('interest.update',$this->id);
    	//end events
    }
    
    protected function clear($string) {
    	$string = preg_replace('/[\/\#\!\@\\\\)\(\?\'\"\:\;\>\<\$\,\.\&\%\*\=\|\{\}\[\]\^\`\~\+\ ]+/ium','-', $string);
    	$string = preg_replace('/([-]{2,})/','-',$string);
    	return trim($string, '-');
    }
    
    protected function renameIfExist() {
    	$uniqueSlug = $this->clear($this->query ? $this->query : $this->title);
    	$table = $this->getTable();
    	$filter = '(`query` LIKE '.$table->getAdapter()->quote($uniqueSlug).' OR `query` LIKE '.$table->getAdapter()->quote($uniqueSlug.'-%').')';
    	if($this->id) { $filter .= ' AND id != ' . $table->getAdapter()->quote($this->id); }
    	$records = $table->fetchAll($filter);
    	$array = array();
    	foreach($records AS $record) {
    		$array[mb_strtolower($record->query, 'utf8')] = mb_strtolower($record->query, 'utf8');
    	}
    	return $this->rename_if_exists($array, mb_strtolower($uniqueSlug, 'utf-8'));
    }
	
	protected function rename_if_exists($array, $query) {
		$i = 0;
		$uniqueSlug = $query;
		while(isset($array[$uniqueSlug])) {
			$uniqueSlug = $query . '-' .++$i;
		}
		return $uniqueSlug;
	}
	
}