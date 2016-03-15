<?php 

return array(
		'interest' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
				'route'    => 'interest/([^/]*)',
				'defaults' => array(
					'module' 		=> 'interest',
					'controller'    => 'index',
					'action'        => 'index',
					'query'			=> '',
				),
				'constraints' => array(1=>'query'),
				'reverse' => 'interest/%s'
			),
		),
    	'interest_c' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
				'route'    => 'interest/([a-z0-9_-]{2,})/(-?[0-9]{1,})/?([^/]*)?',
				'defaults' => array(
					'module' 		=> 'interest',
					'controller'    => 'index',
					'action'        => 'index',
					'user_id'		=> 0,
					'query'			=> ''
				),
				'constraints' => array(1=>'controller', 'interest_id', 'query'),
				'reverse' => 'interest/%s/%d/%s'
			),
		),
		'interest_follow' => array(
			'type'    => '\Interest\Router\UserWithController',
			'options' => array(
				'route'    => 'user/following-interest/(-?[0-9]{1,})/?(.*)?',
				'defaults' => array(
					'module' 		=> 'interest',
					'controller'    => 'following-interest',
					'action'        => 'index',
					'__query__'		=> '',
					'user_id'		=> 0,
					'query'			=> ''
				),
				'constraints' => array(1=>'user_id', 'query'),
				'reverse' => 'user/following-interest/%s/%s'
			),
		),
    );