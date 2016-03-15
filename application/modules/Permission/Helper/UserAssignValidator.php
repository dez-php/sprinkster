<?php

namespace Permission\Helper;

class UserAssignValidator extends \Widget\Form\Phparray\Validator {
	
	public function init()
	{
		$this->_ = new \Translate\Locale('Backend\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function isValid()
	{
		if(0 >= (int) $this->getRequest()->getPost('permission_group_id'))
			$this->errors[] = $this->_('Please select permission group');
	}
	
}