<?php 

$pin_prefix = \Base\Config::get('config_pin_prefix');
if(!$pin_prefix) {
	$pin_prefix = 'pin';
}

//base config
$config = array(
	//admin
	'admin' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => 'prodigy/?',
					'defaults' => array(
							'module' 		=> 'admin',
							'controller'    => 'index',
							'action'        => 'index',
							'___layout___'  => 'admin'
					),
					'reverse' => 'prodigy/'
			),
	),
	'admin_login' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => 'prodigy/login/?',
					'defaults' => array(
							'module' 		=> 'admin',
							'controller'    => 'login',
							'action'        => 'index',
							'query'        	=> '',
							'___layout___'  => 'admin'
					),
					'constraints' => array(1=>'query'),
					'reverse' => 'prodigy/login/?%s'
			),
	),
	'admin_chart' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => 'prodigy/month-chart/?',
					'defaults' => array(
							'module' 		=> 'admin',
							'controller'    => 'index',
							'action'        => 'chart',
							'___layout___'  => 'admin'
					),
					'reverse' => 'prodigy/month-chart/'
			),
	),
	'admin_module' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					// 				'route'    => 'admin/([a-z0-9_]{2,})/?([a-z0-9_]{2,})?/?([^-]*)?',
					'route'    => 'prodigy/([a-z0-9_]{2,})/?([a-z0-9_]{2,})?/?([a-z0-9_]{1,})?',
					'defaults' => array(
							'module' 		=> 'admin',
							'controller'    => 'admin',
							'action'        => 'index',
							'query'        	=> '',
							'___layout___'  => 'admin'
					),
					'constraints' => array(1=>'module','action','query'),
					'reverse' => 'prodigy/%s/%s/?%s'
			),
	),
	'cart_admin' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => 'prodigy/cart/?([a-z0-9_-]{2,})?/?',
					'defaults' => array(
							'module' 		=> 'cart',
							'controller'    => 'admin',
							'action'        => 'index',
							'___layout___'  => 'admin',
							'query'        	=> '',
					),
					'constraints' => array(1=>'action','query'),
					'reverse' => 'prodigy/cart/%s/?%s'
			),
	),
	'cart_status' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => 'prodigy/cart-status/?([a-z0-9_-]{2,})?/?',
					'defaults' => array(
							'module' 		=> 'cart',
							'controller'    => 'admin-status',
							'action'        => 'index',
							'___layout___'  => 'admin',
							'query'        	=> '',
					),
					'constraints' => array(1=>'action','query'),
					'reverse' => 'prodigy/cart-status/%s/?%s'
			),
	),
	'system_page' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => 'prodigy/system_page/([a-z0-9_-]{2,})?/?',
					'defaults' => array(
							'module' 		=> 'page',
							'controller'    => 'admin-system',
							'action'        => 'index',
							'query'        	=> '',
							'___layout___'  => 'admin'
					),
					'constraints' => array(1=>'action','query'),
					'reverse' => 'prodigy/system_page/%s/?%s'
			),
	),
	'admin_report_category' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => 'prodigy/report-category-([a-z0-9_]{2,})/?([a-z0-9_]{2,})?/?',
					'defaults' => array(
							'module' 		=> 'report',
							'controller'    => 'index',
							'action'        => 'index',
							'query'        	=> '',
							'___layout___'  => 'admin'
					),
					'constraints' => array(1=>'controller','action','query'),
					'reverse' => 'prodigy/report-category-%s/%s?%s'
			),
	),
	'admin_report' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => 'prodigy/reports-([a-z0-9_-]{2,})/?([a-z0-9_]{2,})?/?',
					'defaults' => array(
							'module' 		=> 'report',
							'controller'    => 'index',
							'action'        => 'index',
							'query'        	=> '',
							'___layout___'  => 'admin'
					),
					'constraints' => array(1=>'controller','action','query'),
					'reverse' => 'prodigy/reports-%s/%s?%s'
			),
	),
	'admin_system' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => 'prodigy/system/sys-([a-z0-9_-]{2,})/?([a-z0-9_-]{2,})?/?',
					'defaults' => array(
							'module' 		=> 'system',
							'controller'    => 'index',
							'action'        => 'index',
							'query'        	=> '',
							'___layout___'  => 'admin'
					),
					'constraints' => array(0=>'module', 'controller','action','query'),
					'reverse' => 'prodigy/%s/sys-%s/%s/?%s'
			),
	),
    'permissiongroup' => [
        'type'    => '\Core\Router\Regex',
        'options' => [
            'route'    => 'prodigy/permission/group/?',
            'defaults' => [
                'module' 		=> 'permission',
                'controller'    => 'group',
                'action'        => 'index',
                '___layout___'  => 'admin',
            ],
            'reverse' => 'prodigy/permission/group/',
        ],
    ],
	//front
	'html_appcache' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => 'acache/html5/?',
					'defaults' => array(
							'module' 		=> 'aacache',
							'controller'    => 'html5',
							'action'        => 'index'
					),
					'reverse' => 'acache/html5/'
			),
	),
	'activity' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => 'activity/?',
					'defaults' => array(
							'module' 		=> 'activity',
							'controller'    => 'index',
							'action'        => 'index'
					),
					'reverse' => 'activity/'
			),
	),
	'bookmarklet' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => 'bookmarklet/([a-z0-9_-]{2,})/?',
					'defaults' => array(
							'module' 		=> 'bookmarklet',
							'controller'    => 'index',
							'action'        => 'index'
					),
					'constraints' => array(1=>'controller'),
					'reverse' => 'bookmarklet/%s'
			),
	),
	'bookmarklet_guid' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => 'bookmarklet/([a-z0-9_-]{2,})/([a-z0-9-]{8,100})/?',
					'defaults' => array(
							'module' 		=> 'bookmarklet',
							'controller'    => 'index',
							'action'        => 'index',
							'guid'			=> ''
					),
					'constraints' => array(1=>'controller','guid'),
					'reverse' => 'bookmarklet/%s/%s'
			),
	),
	'cart_dashboard_subscriptions' => array(
			'type' => '\Core\Router\Regex',
			'options' => array(
					'route' => 'user/dashboard/subscriptions',
					'defaults' => array(
							'module' => 'cart',
							'controller' => 'dashboard',
							'action' => 'mysubscriptions'
					),
					'reverse' => 'user/dashboard/subscriptions'
			),
	),
	'category' => [
	'type'    => '\Core\Router\Regex',
	'options' => [
	'route'    => 'category/([0-9]{1,})/?(.*)?',
	'defaults' => [
	'module' 		=> 'category',
	'controller'    => 'index',
	'action'        => 'index',
	'category_id'   => 0,
	'__query__'		=> '',
	],
	'constraints' => [ 1=>'category_id','query' ],
	'reverse' => 'category/%d/%s'
	],
	],
	'category_all' => [
	'type'    => '\Core\Router\Regex',
	'options' => [
	'route'    => 'category/all/?',
	'defaults' => [
	'module' 		=> 'category',
	'controller'    => 'index',
	'action'        => 'all',
	],
	'reverse' => 'category/all/'
	],
	],
	'category_c' => [
	'type'    => '\Core\Router\Regex',
	'options' => [
	'route'    => 'category/([a-z0-9_-]{2,})/([0-9]{1,})/?(.*)?',
	'defaults' => array(
			'module' 		=> 'category',
			'controller'    => 'index',
			'action'        => 'index',
			'__query__'		=> '',
			'category_id'		=> 0,
			'query'			=> ''
	),
	'constraints' => array(1=>'controller', 'category_id', 'query'),
	'reverse' => 'category/%s/%d/%s'
	],
	],
	'cron' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => 'cron/([a-z0-9_-]{2,})?/?([a-z0-9_-]{2,})?/?',
					'defaults' => array(
							'module' 		=> 'cron',
							'controller'    => 'index',
							'action'        => 'index'
					),
					'constraints' => array(1=>'controller', 'action'),
					'reverse' => 'cron/%s/%s/'
			),
	),
	'facebook' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => 'facebook/([a-z0-9_-]{2,})/([0-9]{1,})/?(.*)?',
					'defaults' => array(
							'module' 		=> 'facebook',
							'controller'    => 'index',
							'action'        => 'index',
							'query'			=> ''
					),
					'constraints' => array(1=>'controller', 'query'),
					'reverse' => 'facebook/%s/%s'
			),
	),

	'facebook_c' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => 'facebook/([a-z0-9_-]{2,})/?',
					'defaults' => array(
							'module' 		=> 'facebook',
							'controller'    => 'index',
							'action'        => 'index',
					),
					'constraints' => array(1=>'controller'),
					'reverse' => 'facebook/%s'
			),
	),
	'welcome_home' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => '/',
					'defaults' => array(
							'module' 		=> 'home',
							'controller'    => 'index',
							'action'        => 'index'
					),
					'reverse' => ''
			),
	),
	'guid' => [
	'type'    => '\Core\Router\Regex',
	'options' => [
	'route'    => 'guid/?',
	'defaults' => [
	'module'        => 'home',
	'controller'    => 'index',
	'action'        => 'guid'
	],
	'reverse' => 'guid'
	],
	],
	'@' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => '@',
					'defaults' => array(
							'module' 		=> 'home',
							'controller'    => 'index',
							'action'        => 'index'
					),
					'constraints' => array(1=>'module','controller','action'),
					'reverse' => '%s/%s/%s'
			),
	),
	'i18_js' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => 'i18/js/?',
					'defaults' => array(
							'module' 		=> 'i18',
							'controller'    => 'js',
							'action'        => 'index'
					),
					'reverse' => 'i18/js'
			),
	),
	'invite' => array (
			'type' => '\Core\Router\Regex',
			'options' => array (
					'route' => 'invite/?',
					'defaults' => array (
							'module' => 'invite',
							'controller' => 'index',
							'action' => 'index'
					),
					'reverse' => 'invite/'
			)
	),
	'invite_c' => array (
			'type' => '\Core\Router\Regex',
			'options' => array (
					'route' => 'invite/([a-z0-9_-]{2,})/?',
					'defaults' => array (
							'module' => 'invite',
							'controller' => 'index',
							'action' => 'index'
					),
					'constraints' => array (
							1 => 'controller'
					),
					'reverse' => 'invite/%s'
			)
	),
	'newest' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => 'newest/?',
					'defaults' => array(
							'module' 		=> 'newest',
							'controller'    => 'index',
							'action'        => 'index'
					),
					'reverse' => 'newest/'
			),
	),
	'page' => array(
			'type'    => '\Page\Router\Regex',
			'options' => array(
					'route'    => 'page/([0-9]{1,})/?(.*)?',
					'defaults' => array(
							'module' 		=> 'page',
							'controller'    => 'index',
							'action'        => 'index',
							'page_id'   => 0,
							'__query__'		=> '',
					),
					'constraints' => array(1=>'page_id','query'),
					'reverse' => 'page/%s/%s'
			),
	),
	'paymentgateway_my_providers' => [
	'type'    => '\Core\Router\Regex',
	'options' => [
	'route'    => 'payment-providers/?',
	'defaults' => [
	'module' 		=> 'paymentgateway',
	'controller'    => 'index',
	'action'        => 'index',
	],
	'reverse' => 'payment-providers/',
	],
	],

	'paymentgateway_dashboard' => array(
			'type' => '\Core\Router\Regex',
			'options' => array(
					'route' => 'user/dashboard/payment-providers',
					'defaults' => array(
							'module' => 'paymentgateway',
							'controller' => 'index',
							'action' => 'index'
					),
					'constraints' => array(1 => 'controller', 'action'),
					'reverse' => 'user/dashboard/payment-providers'
			),
	),

	'paymentgateway_c' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => 'cart/([a-z0-9_-]{2,})?/?',
					'defaults' => array(
							'module' 		=> 'paymentgateway',
							'controller'    => 'index',
							'action'        => 'index'
					),
					'constraints' => array(1=>'controller'),
					'reverse' => 'cart/%s/'
			),
	),
	'paymentgateway_c_a' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => 'cart/([a-z0-9_-]{2,})?/?([a-z0-9_-]{2,})/?',
					'defaults' => array(
							'module' 		=> 'paymentgateway',
							'controller'    => 'index',
							'action'        => 'index'
					),
					'constraints' => array(1=>'controller', 'action'),
					'reverse' => 'cart/%s/%s/'
			),
	),

	// pay notifications
	'paymentgateway-return_url' => [
	'type' => '\Core\Router\Regex',
	'options' => [
	'route' => 'p/pay/(.+)/?',
	'defaults' => [
	'module' => 'paymentgateway',
	'controller' => 'payment',
	'action' => 'pay',
	'order_number' => 0,
	],
	'constraints' => [ 1 => 'order_number' ],
	'reverse' => 'p/pay/%s',
	],
	],

	'paymentgateway-notify_url' => [
	'type' => '\Core\Router\Regex',
	'options' => [
	'route' => 'p/notify/?(?:(.+)/?)?',
	'defaults' => [
	'module' => 'paymentgateway',
	'controller' => 'payment',
	'action' => 'notify',
	'order_number' => 0,
	],
	'constraints' => [ 1 => 'order_number' ],
	'reverse' => 'p/notify/%s',
	],
	],

	'paymentgateway-manual_url' => [
	'type' => '\Core\Router\Regex',
	'options' => [
	'route' => 'p/order/pay/(.+)/?',
	'defaults' => [
	'module' => 'paymentgateway',
	'controller' => 'payment',
	'action' => 'manual',
	'order_number' => 0,
	],
	'constraints' => [ 1 => 'order_number' ],
	'reverse' => 'p/order/pay/%s',
	],
	],

	'paymentgateway-after_url' => [
	'type' => '\Core\Router\Regex',
	'options' => [
	'route' => 'p/order/(.+)/?$',
	'defaults' => [
	'module' => 'paymentgateway',
	'controller' => 'payment',
	'action' => 'after',
	'order_number' => 0,
	],
	'constraints' => [ 1 => 'order_number' ],
	'reverse' => 'p/order/%s',
	],
	],

	'paymentgateway-ordercancel_url' => [
	'type' => '\Core\Router\Regex',
	'options' => [
	'route' => 'p/cancel/(.+)/?',
	'defaults' => [
	'module' => 'paymentgateway',
	'controller' => 'payment',
	'action' => 'ordercancel',
	'order_number' => 0,
	],
	'constraints' => [ 1 => 'order_number' ],
	'reverse' => 'p/cancel/%s',
	],
	],

	'paymentgateway-success_url' => [
	'type' => '\Core\Router\Regex',
	'options' => [
	'route' => 'p/success/(.+)/?',
	'defaults' => [
	'module' => 'paymentgateway',
	'controller' => 'payment',
	'action' => 'success',
	'order_number' => 0,
	],
	'constraints' => [ 1 => 'order_number' ],
	'reverse' => 'p/success/%s',
	],
	],
	'paypal' => [
	'type'    => '\Core\Router\Regex',
	'options' => [
	'route'    => 'paypal/([a-z0-9_-]{2,})?/?([a-z0-9_-]{2,})/?',
	'defaults' => [
	'module' 		=> 'paypal',
	'controller'    => 'index',
	'action'        => 'index'
	],
	'constraints' => [ 1 => 'controller', 'action' ],
	'reverse' => 'paypal/%s/%s/'
	],
	],
	'pin' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => $pin_prefix . '/([0-9]{1,})/?(.*)?',
					'defaults' => array(
							'module' 		=> 'pin',
							'controller'    => 'index',
							'action'        => 'index',
							'__query__'		=> '',
							'query'			=> '',
							'pin_id'		=> 0
					),
					'constraints' => array(1=>'pin_id','query'),
					'reverse' => $pin_prefix . '/%d/%s'
			),
	),
	'search_pin' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => 'search/?',
					'defaults' => array(
							'module' 		=> 'pin',
							'controller'    => 'search',
							'action'        => 'index',
							'query' 		=> ''
					),
					'constraints' => array(1=>'query'),
					'reverse' => 'search/?query=%s'
			),
	),

	'pin_c' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => $pin_prefix . '/([a-z0-9_-]{2,})/([0-9]{1,})/?(.*)?',
					'defaults' => array(
							'module' 		=> 'pin',
							'controller'    => 'index',
							'action'        => 'index',
							'__query__'		=> '',
							'pin_id'		=> 0,
							'query'			=> ''
					),
					'constraints' => array(1=>'controller', 'pin_id', 'query'),
					'reverse' => $pin_prefix . '/%s/%d/%s'
			),
	),

	'pin_c_a' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => $pin_prefix . '/([a-z0-9_-]{2,})/([a-z0-9_-]{2,})/([0-9]{1,})/?(.*)?',
					'defaults' => array(
							'module' 		=> 'pin',
							'controller'    => 'index',
							'action'        => 'index',
							'__query__'		=> '',
							'pin_id'		=> 0,
							'query'			=> ''
					),
					'constraints' => array(1=>'controller', 'action', 'pin_id', 'query'),
					'reverse' => $pin_prefix . '/%s/%s/%d/%s'
			),
	),
	'popular' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => 'popular/?',
					'defaults' => array(
							'module' 		=> 'popular',
							'controller'    => 'index',
							'action'        => 'index'
					),
					'reverse' => 'popular/'
			),
	),
	'popular_c' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => 'popular/([a-z0-9_-]{2,})/?',
					'defaults' => array(
							'module' 		=> 'popular',
							'controller'    => 'index',
							'action'        => 'index',
							'filter'        => 'today'
					),
					'constraints' => array(1=>'filter'),
					'reverse' => 'popular/%s/'
			),
	),
	'autocomplete_search' => [
	'type'    => '\Core\Router\Regex',
	'options' => [
	'route'    => 'search/hints?$',
	'defaults' => [
	'module' 		=> 'search',
	'controller'    => 'search',
	'action'        => 'autocomplete',
	],
	'reverse' => 'search/hints',
	],
	],
	'settings' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => 'settings/?([\d]{1,})?/?',
					'defaults' => array(
							'module' 		=> 'settings',
							'controller'    => 'index',
							'action'        => 'index',
							'user_id' 		=> ''
					),
					'constraints' => array(1=>'user_id'),
					'reverse' => 'settings/%d'
			),
	),

	'settings_c' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => 'settings/([a-z0-9_-]{2,})/?([\d]{1,})?/?',
					'defaults' => array(
							'module' 		=> 'settings',
							'controller'    => 'index',
							'action'        => 'index',
							'user_id' 		=> ''
					),
					'constraints' => array(1=>'controller','user_id'),
					'reverse' => 'settings/%s/%d'
			),
	),
	'source' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => 'source/([0-9]{1,})/?(.*)?',
					'defaults' => array(
							'module' 		=> 'source',
							'controller'    => 'index',
							'action'        => 'index',
							'source_id'		=> 0,
							'query'			=> '',
					),
					'constraints' => array(1=>'source_id','query'),
					'reverse' => 'source/%d/%s'
			),
	),
	'twitter' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => 'twitter/([a-z0-9_-]{2,})/([0-9]{1,})/?(.*)?',
					'defaults' => array(
							'module' 		=> 'twitter',
							'controller'    => 'index',
							'action'        => 'index',
							'query'			=> ''
					),
					'constraints' => array(1=>'controller', 'query'),
					'reverse' => 'twitter/%s/%s'
			),
	),

	'twitter_c' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => 'twitter/([a-z0-9_-]{2,})/?',
					'defaults' => array(
							'module' 		=> 'twitter',
							'controller'    => 'index',
							'action'        => 'index',
					),
					'constraints' => array(1=>'controller'),
					'reverse' => 'twitter/%s'
			),
	),
	'uploadpin' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => 'uploadpin/?',
					'defaults' => array(
							'module' 		=> 'uploadpin',
							'controller'    => 'index',
							'action'        => 'index'
					),
					'reverse' => 'uploadpin/'
			),
	),
	 
	'uploadpin_c' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => 'uploadpin/([^/]*)?/?',
					'defaults' => array(
							'module' 		=> 'uploadpin',
							'controller'    => 'index',
							'action'        => 'index'
					),
					'constraints' => array(1=>'controller'),
					'reverse' => 'uploadpin/%s/'
			),
	),
	'urlpin' => [
	'type'                          => '\Core\Router\Regex',
	'options' => [
	'route'                     => 'urlpin/?',
	'defaults' => [
	'module'                => 'urlpin',
	'controller'            => 'index',
	'action'                => 'index'
	],
	'reverse'                   => 'urlpin/'
	],
	],

	'urlpin_c' => [
	'type' => '\Core\Router\Regex',
	'options' => [
	'route'                     => 'urlpin/([^/]*)/?([^/]*)?',
	'defaults' => [
	'module'            => 'urlpin',
	'controller'        => 'index',
	'action'            => 'index',
	'query'             => ''
	],
	'constraints'           => [ 1 => 'controller', 'query' ],
	'reverse'               => 'urlpin/%s/%s'
	],
	],
	'user' => array(
			'type'    => '\User\Router\User',
			'options' => array(
					'route'    => 'user/([0-9]{1,})/?(.*)?',
					'defaults' => array(
							'module' 		=> 'user',
							'controller'    => 'index',
							'action'        => 'index',
							'__query__'		=> '',
							'user_id'		=> 0
					),
					'constraints' => array(1=>'user_id','query'),
					'reverse' => 'user/%s/%s'
			),
	),
	'user_c' => array(
			'type'    => '\User\Router\UserWithController',
			'options' => array(
					'route'    => 'user/([a-z0-9_-]{2,})/(-?[0-9]{1,})/?(.*)?',
					'defaults' => array(
							'module' 		=> 'user',
							'controller'    => 'index',
							'action'        => 'index',
							'__query__'		=> '',
							'user_id'		=> 0,
							'query'			=> ''
					),
					'constraints' => array(1=>'controller', 'user_id', 'query'),
					'reverse' => 'user/%s/%s/%s'
			),
	),
	'user_c_a' => array(
			'type'    => '\User\Router\UserWithControllerAction',
			'options' => array(
					'route'    => 'user/([a-z0-9_-]{2,})/([a-z0-9_-]{2,})/([0-9]{1,})/?(.*)?',
					'defaults' => array(
							'module' 		=> 'user',
							'controller'    => 'index',
							'action'        => 'index',
							'__query__'		=> '',
							'user_id'		=> 0,
							'query'			=> ''
					),
					'constraints' => array(1=>'controller', 'action', 'user_id', 'query'),
					'reverse' => 'user/%s/%s/%s/%s'
			),
	),

	'dashboard' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => 'dashboard',
					'defaults' => array(
							'module' 		=> 'user',
							'controller'    => 'dashboard',
							'action'        => 'index',
							'__query__'		=> '',
							'user_id'		=> 0
					),
					'constraints' => array(),
					'reverse' => 'dashboard'
			),
	),

	'search_user' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => 'search/user/?',
					'defaults' => array(
							'module' 		=> 'user',
							'controller'    => 'search',
							'action'        => 'index',
							'query' 		=> ''
					),
					'constraints' => array(1=>'query'),
					'reverse' => 'search/user/?query=%s'
			),
	),
	'videos' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => 'videos/?',
					'defaults' => array(
							'module' 		=> 'videos',
							'controller'    => 'index',
							'action'        => 'index'
					),
					'reverse' => 'videos/'
			),
	),
	'wishlist' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => 'collection/([0-9]{1,})/?(.*)?',
					'defaults' => array(
							'module' 		=> 'wishlist',
							'controller'    => 'index',
							'action'        => 'index',
							'wishlist_id'		=> 0,
							'__query__'		=> '',
					),
					'constraints' => array(1=>'wishlist_id','query'),
					'reverse' => 'collection/%d/%s'
			),
	),
	'search_wishlist' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => 'search/collection/?',
					'defaults' => array(
							'module' 		=> 'wishlist',
							'controller'    => 'search',
							'action'        => 'index',
							'query' 		=> ''
					),
					'constraints' => array(1=>'query'),
					'reverse' => 'search/collection/?query=%s'
			),
	),

	'wishlist_c' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => 'collection/([a-z0-9_-]{2,})/([0-9]{1,})/?(.*)?',
					'defaults' => array(
							'module' 		=> 'wishlist',
							'controller'    => 'index',
							'action'        => 'index',
							'__query__'		=> '',
							'wishlist_id'		=> 0,
							'query'			=> ''
					),
					'constraints' => array(1=>'controller', 'wishlist_id', 'query'),
					'reverse' => 'collection/%s/%d/%s'
			),
	),
	'createwishlist' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => 'collection/create/?',
					'defaults' => array(
							'module' 		=> 'wishlist',
							'controller'    => 'create',
							'action'        => 'index'
					),
					'reverse' => 'collection/create'
			),
	),
	'createwishlist_simple' => array(
			'type'    => '\Core\Router\Regex',
			'options' => array(
					'route'    => 'collection/simple/?',
					'defaults' => array(
							'module' 		=> 'wishlist',
							'controller'    => 'create',
							'action'        => 'simple'
					),
					'reverse' => 'collection/simple'
			),
	),

);

if(\Base\Config::get('welcome_status') && \Base\Config::get('welcome_inner_pages')) {
	if(\Core\Base\Action::getInstance()->isModuleAccessible('Homepage') && \Base\Config::get('config_home_page_display_type') && \Base\Config::get('config_home_page_display_type') != 'base') {
		$config['welcome_home'] = array(
				'type'    => '\Core\Router\Regex',
				'options' => array(
						'route'    => 'home/?',
						'defaults' => array(
								'module' 		=> 'homepage',
								'controller'    => 'index',
								'action'        => 'index'
						),
						'reverse' => 'home/'
				),
		);
	} else {
		$config['welcome_home'] = array(
				'type'    => '\Core\Router\Regex',
				'options' => array(
						'route'    => 'home/?',
						'defaults' => array(
								'module' 		=> 'home',
								'controller'    => 'index',
								'action'        => 'index'
						),
						'reverse' => 'home/'
				),
		);
	}
}

return $config;