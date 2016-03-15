<?php

namespace Language;

class LanguageRow extends \Core\Db\Table\Row {
	

    /**
     * Allows post-insert logic to be applied to row.
     * Subclasses may override this method.
     *
     * @return void
     */
    protected function _postInsert()
    {
    	set_time_limit(0);
    	ignore_user_abort(true);
    	
    	$db = $this->getTable()->getAdapter();
    	
    	$tables = $this->generateInsertQueryes(\Base\Config::get ( 'language_id' ),$this->id);
    	foreach ($tables AS $query) {
    		$db->query($query);
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
    	set_time_limit(0);
    	ignore_user_abort(true);
    }

    /**
     * Allows post-delete logic to be applied to row.
     * Subclasses may override this method.
     *
     * @return void
     */
    protected function _postDelete()
    {
    	set_time_limit(0);
    	ignore_user_abort(true);
    	
    	if($this->id == \Base\Config::get ( 'language_id' )) {
    		return;
    	}
    	
    	$db = $this->getTable()->getAdapter();
    	
    	$tables = $this->generateDeleteQueryes($this->id);
    	foreach ($tables AS $query) {
    		$db->query($query);
    	}
    }

    protected function generateInsertQueryes($default_language_id, $language_id)
    {
        $db = $this->getTable()->getAdapter();
        $tables = $db->listTables();
        $insert = array();

        foreach ($tables AS $table) {
            if (in_array($table, array('user')))
                continue;
            $rows = array_keys($db->describeTable($table));
            if ($rows) {
                if (in_array('language_id', $rows)) {
                    $insert[$table] = "INSERT INTO `" . $table . "` (`" . implode('`, `', $rows) . "`) SELECT ";
                    foreach ($rows AS $key => $row) {
                        if($row == 'id') {
                        	$insert[$table] .= ($key ? ', ' : '') . 'NULL';
                        } else if ($row == 'language_id') {
                            $insert[$table] .= ($key ? ', ' : '') . (int) $language_id;
                        } else {
                            $insert[$table] .= ($key ? ', ' : '') . '`' . $row . '`';
                        }
                    }
                    $insert[$table] .= "FROM `" . $table . "` WHERE `language_id` = " . $default_language_id;
                }
            }
        }

        return $insert;
    }

    protected function generateDeleteQueryes($language_id)
    {
        $db = $this->getTable()->getAdapter();
        $tables = $db->listTables();
        $delete = array();

        foreach ($tables AS $table) {
            if (in_array($table, array('user')))
                continue;
            $rows = array_keys($db->describeTable($table));
            if ($rows) {
                if (in_array('language_id', $rows)) {
                    $delete[$table] = "DELETE FROM `" . $table . "` WHERE `language_id` = " . $language_id;
                }
            }
        }

        return $delete;
    }
	
}