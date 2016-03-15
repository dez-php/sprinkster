<?php

namespace Pin;

class PinCommentRow extends \Core\Db\Table\Row {
	
	public function getUserFullname() {
    	$username_method_show = \Base\Config::get('username_show');
    	if($username_method_show == 'fullname') {
    		return $this->firstname . ' ' . $this->lastname;
    	} else if($username_method_show == 'fullname-desc') {
    		return $this->lastname . ' ' . $this->firstname;
    	} else if($username_method_show == 'firstname') {
    		return $this->firstname;
    	} else {
    		return $this->username;
    	}
    }

    /**
     * Allows post-insert logic to be applied to row.
     * Subclasses may override this method.
     *
     * @return void
     */
    protected function _postInsert()
    {
    	$pinTable = new \Pin\Pin();
    	$pin = $pinTable->fetchRow(array('id = ?' => $this->pin_id));
    	if($pin) {
    		$pinTable->update(array(
    			'comments' => $this->getTable()->countByPinId($this->pin_id)
    		), array('id = ?' => $this->pin_id));
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
    $pinTable = new \Pin\Pin();
    	$pin = $pinTable->fetchRow(array('id = ?' => $this->pin_id));
    	if($pin) {
    		$pinTable->update(array(
    			'comments' => $this->getTable()->countByPinId($this->pin_id)
    		), array('id = ?' => $this->pin_id));
    	}
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
    	$pin = $pinTable->fetchRow(array('id = ?' => $this->pin_id));
    	if($pin) {
    		$pinTable->update(array(
    			'comments' => $this->getTable()->countByPinId($this->pin_id)
    		), array('id = ?' => $this->pin_id));
    	}
    }
    
    
	
}