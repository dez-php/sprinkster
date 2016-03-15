<?php
namespace Base\Traits;

trait XFormCmd {

	public function getXFormCmd($key = NULL)
	{
		$request = \Core\Http\Request::getInstance();
		return md5($key . $request->getServer('HTTP_USER_AGENT')) . '-' . substr(md5($request->getServer('SERVER_ADDR')), 5, 10);
	}
}