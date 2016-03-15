<?php 

return array (
				'conversation' => array (
						'type' => '\Core\Router\Regex',
						'options' => array (
								'route' => 'conversation/?',
								'defaults' => array (
										'module' => 'conversation',
										'controller' => 'index',
										'action' => 'index' 
								),
								'reverse' => 'conversation/' 
						) 
				),
				
				'conversation_p' => array (
						'type' => '\Core\Router\Regex',
						'options' => array (
								'route' => 'conversation/question/([0-9]{1,})/?(.*)?',
								'defaults' => array (
										'module' => 'conversation',
										'controller' => 'question',
										'action' => 'index',
										'pin_id' => 0,
										'query' => '' 
								),
								'constraints' => array (
										1 => 'pin_id',
										'query' 
								),
								'reverse' => 'conversation/question/%d/%s' 
						) 
				),
				
				'conversation_u' => array (
						'type' => '\Core\Router\Regex',
						'options' => array (
								'route' => 'conversation/question-user/([0-9]{1,})/?(.*)?',
								'defaults' => array (
										'module' => 'conversation',
										'controller' => 'question-user',
										'action' => 'index',
										'user_id' => 0,
										'query' => '' 
								),
								'constraints' => array (
										1 => 'user_id',
										'query' 
								),
								'reverse' => 'conversation/question-user/%d/%s' 
						) 
				),
				
				'conversation_c' => array (
						'type' => '\Core\Router\Regex',
						'options' => array (
								'route' => 'conversation/([a-z0-9_-]{2,})/(-?[0-9]{1,})/?(.*)?',
								'defaults' => array (
										'module' => 'conversation',
										'controller' => 'index',
										'action' => 'index',
										'conversation_id' => 0,
										'query' => '' 
								),
								'constraints' => array (
										1 => 'controller',
										'conversation_id',
										'query' 
								),
								'reverse' => 'conversation/%s/%d/%s' 
						) 
				) 
		);