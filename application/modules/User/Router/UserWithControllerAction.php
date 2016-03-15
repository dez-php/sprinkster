<?php

namespace User\Router;

class UserWithControllerAction extends \User\Router\Regex {
	
	protected $regexp = 'user/([a-z0-9_-]{2,})/([a-z0-9_-]{2,})/(?<username>[^/]{1,})/?';

}