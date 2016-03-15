<?php

namespace Base\Widget;

use \Base\Traits\AcceptedResponseDetection;

abstract class PermissionWidget extends \Core\Base\Widget {

	const PermissionSeparator = '.';

	use AcceptedResponseDetection;

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

		$wrong_content_check = ob_get_contents();

		// We are going to put JSON data, but we have no JSON content - it might be an error - Normal output
		if($wrong_content_check && !json_decode($wrong_content_check))
			return $this->invalidate('onInvalidRender');			

		$request->setQuery('callback', 'alert');
		$this->responseJsonCallback('Access denied.');
	}

	public function onInvalidRender()
	{
	}

}