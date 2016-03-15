<?php
return [
	'production' => [
		'system_version' => '4.0',
		
		'db' => [
			'adapter'                       => 'MYSQLi',
			'params' => [
				'host'                      => 'sprinkster-main.c8mkaxaoduxj.us-east-1.rds.amazonaws.com',
				'username'                  => 'sprinkster_dbu',
				'password'                  => 'w2SeyvMwfKnhTd8hTB',
				'dbname'                    => 'sprinkster_db',
				'charset'					=> 'utf8',
// 				'profiler'                  => true,
			],
		],
		
		'phpSettings' => [
			'display_startup_errors'        => 1,
			'display_errors'                => 1,
			'error_reporting'               => 1,
//			'memory_limit'                  => '1024M',
//			'max_execution_time'            => 60,
// 			'date'                          => [ 'timezone' => 'Europe/Sofia' ],
			'default_charset'               => 'UTF-8'
		],
		
		'frontController' => [
			'moduleDirectory'               => APPLICATION_PATH . DIRECTORY_SEPARATOR . 'modules',
			'themeDirectory'                => APPLICATION_PATH . DIRECTORY_SEPARATOR . 'themes',
			'theme'                         => 'default',
			'defaultModule'                 => 'home',
			'display_exceptions'            => 1
		],

		'view_manager' => [
			'display_exceptions'            => false,
			'template_map' => [
				'layout'           			=> 'Home/layout.phtml',
				'error/404'               	=> 'error\error\not_found',
				// 'error/index'            => 'home\error\index',
				// 'error/php'              => 'home\error\php',
			],
		],	
	],
	
	'development : production' => [
		'phpSettings' => [
			'display_startup_errors'        => 1,
			'display_errors'                => 1,
			'error_reporting'               => E_ALL & ~E_STRICT,
		],
		'view_manager' => [ 'display_exceptions' => 1 ],
	],
]; 