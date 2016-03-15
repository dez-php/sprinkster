<?php 

return array(
		'multilanguagetranslate' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
				'route'    => 'admin/multilanguagetranslate/([a-z0-9_-]{2,})?/?',
				'defaults' => array(
					'module' 		=> 'multilanguage',
					'controller'    => 'translate',
					'action'        => 'index',
					'query'        	=> '',
					'___layout___'  => 'admin'
				),
				'constraints' => array(1=>'action','query'),
				'reverse' => 'admin/multilanguagetranslate/%s/?%s'
			),
		),
		'multilanguage' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
				'route'    => 'multilanguage/?',
				'defaults' => array(
					'module' 		=> 'multilanguage',
					'controller'    => 'index',
					'action'        => 'index'
				),
				'reverse' => 'multilanguage/'
			),
		),
		'multilanguage_c' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
				'route'    => 'multilanguage/([a-z0-9_-]{2,})/?',
				'defaults' => array(
					'module' 		=> 'multilanguage',
					'controller'    => 'index',
					'action'        => 'index'
				),
				'constraints' => array(1=>'controller'),
				'reverse' => 'multilanguage/%s/'
			),
		),
		'multilanguage_c_a' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
				'route'    => 'multilanguage/([a-z0-9_-]{2,})/([a-z0-9_-]{2,})/?',
				'defaults' => array(
					'module' 		=> 'multilanguage',
					'controller'    => 'index',
					'action'        => 'index'
				),
				'constraints' => array(1=>'controller','action'),
				'reverse' => 'multilanguage/%s/%s/'
			),
		),
    );