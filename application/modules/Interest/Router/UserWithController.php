<?php

namespace Interest\Router;

class UserWithController extends \User\Router\Regex {
	
	protected $regexp = 'user/following-interest/(?<username>[^/]{1,})/?';
	
}