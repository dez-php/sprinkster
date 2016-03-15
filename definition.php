<?php

// Namespace Separator
defined('NAMESPACE_SEPARATOR') || define('NAMESPACE_SEPARATOR', '\\');

// Short Directory Separator
defined('DS') || define('DS', DIRECTORY_SEPARATOR);

// Short Namespace Separator
defined('NS') || define('NS', NAMESPACE_SEPARATOR);

// Application Path
defined('APPLICATION_PATH') || define('APPLICATION_PATH', realpath(dirname(__FILE__) . DS . 'application' . DS));

// Modules Path
defined('MODULES_PATH') || define('MODULES_PATH', realpath(dirname(__FILE__) . DS . 'application' . DS . 'modules'));

// Base Path
defined('BASE_PATH') || define('BASE_PATH', realpath(dirname(__FILE__)));

// Vendor Path
defined('VENDOR_PATH') || define('VENDOR_PATH', realpath(dirname(__FILE__) . DS . 'vendor'));

// Modification Path
defined('MODIFICATION_PATH') || define('MODIFICATION_PATH', APPLICATION_PATH . DS . 'modifications' . DS);

// Environment
if(!defined('APPLICATION_ENV'))
{
	if(getenv('APPLICATION_ENV'))
		define('APPLICATION_ENV', getenv('APPLICATION_ENV'));
	else if(isset($_SERVER['APPLICATION_ENV']) && $_SERVER['APPLICATION_ENV'])
		define('APPLICATION_ENV', $_SERVER['APPLICATION_ENV']);
	else
		define('APPLICATION_ENV', 'production');
}

function d($params, $return = FALSE)
{
	$args = func_get_args();
	$result = '';
	$return = FALSE;

	if(2 <= count($args) && is_bool($args[ count($args) - 1 ]))
	{
		$return = $args[ count($args) - 1 ];
		array_pop($args);
	}
	
	foreach($args as $arg)
		$result .= '<pre>' . print_r($arg, TRUE) . '</pre>';

	return $return ? $result : print($result);
}

function dbProfiler() {
	$request = \Core\Http\Request::getInstance();
	if($request->isXmlHttpRequest() && $request->getQuery('callback'))
		return;
	$db = \Core\Db\Init::getDefaultAdapter();
	$profiler = $db->getProfiler();
	
	if($profiler->getEnabled()) {
		echo '<table border="1">';
		$total = 0;
		foreach($profiler->getQueryProfiles() AS $r => $query) {
			$sel = $query->getQuery();
			$time = abs(sprintf('%f',$query->getElapsedSecs()));
			echo '<tr '.($time > 0.01 ? 'style="background:#ff0000;"' : '').'>';
			echo '<td width="1">'.$r.'</td>';
			echo '<td style="width:50%;">EXPLAIN '.$sel.'</td>';
			$total += $time;
			echo '<td>' . $query->getTraceHtml() . '</td>';
			echo '<td width="1">'.$time.'</td>';
			echo '</tr>';
		}
		echo '<tr>';
		echo '<td>&nbsp;</td>';
		echo '<td>&nbsp;</td>';
		echo '<td>&nbsp;</td>';
		echo '<td><b>'.$total.'</b></td>';
		echo '</tr>';
		echo '</table>';
	}
}