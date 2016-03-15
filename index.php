<?php
// Decline static file requests back to the PHP built-in webserver
if (php_sapi_name() === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)))
	return FALSE;

require_once(__DIR__ . '/definition.php');

if(!is_file(APPLICATION_PATH . '/config/application.php') && is_dir(BASE_PATH . '/install/')) {
	include_once 'Core/Http/Request.php';
	header('Location: ' . \Core\Http\Request::getInstance()->getBaseUrl() . 'install/');
}

require_once 'Core/Base/Init.php';
require_once 'init_autoloader.php';

$application = \Core\Base\Init::getInstance(
    APPLICATION_ENV,
    APPLICATION_PATH . '/config/application.php',
    isset($argv) ? $argv : null,
	__DIR__
);

//set config's data Recursive
if(file_exists(APPLICATION_PATH . '/config/globals/')) {
	$Directory = new RecursiveDirectoryIterator(APPLICATION_PATH . '/config/globals/');
	$Iterator = new RecursiveIteratorIterator($Directory);
	$objects  = new RegexIterator($Iterator, '/^.+\.php$/i');
	foreach($objects as $name => $object){
		try {
			$options = include_once $name;
			if($options && is_array($options)) {
				$application->setOptions($options);
			}
		} catch (\Exception $e) {}
	}
}

//dispatch application
// $request = $application->getFrontController()->getRequest();
// if(!trim($request->getUri(), '/') && strlen($widget = $request->getQuery('widget')) > 4) {
// 	$request->unsetParam('widget')->unsetParam('waction');
// 	$application->dispatchWidget($widget);
// } else {
// 	$application->dispatch();
	
// 	//db profile
// 	dbProfiler();
// }

$application->dispatch();

//db profile
dbProfiler();