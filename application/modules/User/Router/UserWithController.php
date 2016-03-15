<?php

namespace User\Router;

class UserWithController extends \User\Router\Regex {
	
	protected $regexp = 'user/([a-z0-9_-]{2,})/(?<username>[^/]{1,})/?';

}