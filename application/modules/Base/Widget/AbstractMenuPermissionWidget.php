<?php

namespace Base\Widget;

abstract class AbstractMenuPermissionWidget extends \Base\Widget\PermissionWidget {

	protected $is_group = 0;

	const PermissionSeparator = '.';

	public function preDispatch()
	{

		$request = $this->getRequest();

		$permission = strtolower(str_replace('\\', '.', get_class($this)));

		// User has the required permission
		if(\Permission\Permission::capable($permission))
			return;
		
		// Normal request - forward to controller action response
		if(in_array(self::detect($request), [ self::$ResponseFormatPlain, self::$ResponseFormatUnknown ]))
			return $this->invalidate('onInvalidRender');
			
		$request->setQuery('callback', 'alert');

		$this->responseJsonCallback('Access denied.');

		exit;
	}

	/**
	 * @param number|bool $is_group
	 * @return \Base\AbstractMenu
	 */
	public function setIs_group($is_group) {
		$this->is_group = $is_group;
		return $this;
	}

}