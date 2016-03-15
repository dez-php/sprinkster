<?php

namespace User\Router;

class User extends \User\Router\Regex {
	
	protected $regexp = 'user/(?<username>[^/]{1,})/?';
	
}