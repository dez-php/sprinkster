<?php 

return array(
		'tag' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
				'route'    => 'tag/?',
				'defaults' => array(
					'module' 		=> 'tag',
					'controller'    => 'index',
					'action'        => 'index',
					'leter'		=> 'a'
				),
				'reverse' => 'tag/'
			),
		),
		'tag_leter' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
				'route'    => 'tag/([a-z0-9_-]{1}|0)/?',
				'defaults' => array(
					'module' 		=> 'tag',
					'controller'    => 'index',
					'action'        => 'index',
					'leter'		=> 'a'
				),
				'constraints' => array(1=>'leter'),
				'reverse' => 'tag/%s/'
			),
		),
		'tag_q' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
				'route'    => 'tag/([0-9]{1,})/?(.*)?',
				'defaults' => array(
					'module' 		=> 'tag',
					'controller'    => 'index',
					'action'        => 'pins',
					'__query__'		=> '',
					'tag_id'		=> 0
				),
				'constraints' => array(1=>'tag_id','query'),
				'reverse' => 'tag/%d/%s'
			),
		),
		'search_tag' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
				'route'    => 'search/tag/?',
				'defaults' => array(
					'module' 		=> 'tag',
					'controller'    => 'search',
					'action'        => 'index',
					'query' 		=> ''
				),
				'constraints' => array(1=>'query'),
				'reverse' => 'search/tag/?query=%s'
			),
		),
    		'admin_tag_letter' => array(
    				'type'    => '\Core\Router\Regex',
    				'options' => array(
    						'route'    => 'admin/tag-letter/?([a-z0-9_-]{2,})?/?',
    						'defaults' => array(
    								'module' 		=> 'tag',
    								'controller'    => 'admin-letter',
    								'action'        => 'index',
    								'query'        	=> '',
    								'___layout___'  => 'admin'
    						),
    						'constraints' => array(1=>'action'),
    						'reverse' => 'admin/tag-letter/%s/'
    				),
    		),
    );