<?php 

return array(
    		
		'youtubecrawler' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
				'route'    => 'youtube_crawler/?([a-z0-9_-]{2,})?/?',
				'defaults' => array(
					'module' 		=> 'youtubecrawler',
					'controller'    => 'cron',
					'action'        => 'index'
				),
				'constraints' => array(1=>'action'),
				'reverse' => 'youtube_crawler/%s/'
			),
		),
    );