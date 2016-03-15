<?php

namespace Pin;

class PinLikeRow extends \Core\Db\Table\Row {
    /**
     * Allows post-insert logic to be applied to row.
     * Subclasses may override this method.
     *
     * @return void
     */
    protected function _postInsert()
    {
    	$pinTable = new \Pin\Pin();
    	$pinTable->update(array(
    		'likes' => $this->getTable()->countByPinId_Status($this->pin_id,1)		
    	), array('id=?'=>$this->pin_id));
    	$userTable = new \User\User();
    	$userTable->update(array(
    			'likes' => $this->getTable()->countByUserId_Status($this->user_id,1)
    	), array('id=?'=>$this->user_id));
    }

    /**
     * Allows post-delete logic to be applied to row.
     * Subclasses may override this method.
     *
     * @return void
     */
    protected function _postDelete()
    {
    	$pinTable = new \Pin\Pin();
    	$pinTable->update(array(
    		'likes' => $this->getTable()->countByPinId_Status($this->pin_id,1)		
    	), array('id=?'=>$this->pin_id));
    	$userTable = new \User\User();
    	$userTable->update(array(
    			'likes' => $this->getTable()->countByUserId_Status($this->user_id,1)
    	), array('id=?'=>$this->user_id));
    }
	
}