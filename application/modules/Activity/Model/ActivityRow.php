<?php

namespace Activity;

class ActivityRow extends \Core\Db\Table\Row {
    
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
	
}