<?php

namespace Core\Router;

class Router {
	
	/**
	 * Array of routes to match against
	 *
	 * @var array
	 */
	protected $_routes = array ();
	
	/**
	 * Global parameters given to all routes
	 *
	 * @var array
	 */
	protected $_globalParams = array ();
	protected $_currentRoute;
	protected $_defaults = array ();
	protected $frontController;
	
	/**
	 *
	 * @var Core\Router\Router
	 */
	private static $_instance;
	
	/**
	 *
	 * @param array $routes        	
	 * @return \Core\Router\Router
	 */
	public static function getInstance($routes = array()) {
		if (self::$_instance == null) {
			self::$_instance = new self ( $routes );
		}
		return self::$_instance;
	}
	
	/**
	 * Constructor
	 *
	 * @param array $routes        	
	 * @return void
	 */
	public function __construct(array $routes = array()) {
		$this->addRoutes ( $routes );
		$this->frontController = \Core\Base\Front::getInstance();
	}
	
	/**
	 * Add route to the route chain
	 *
	 * If route contains method setRequest(), it is initialized with a request
	 * object
	 *
	 * @param string $name
	 *        	Name of the route
	 * @param \Core\Router\AbstractRouter $route
	 *        	Instance of the route
	 * @return \Core\Router\Router
	 */
	public function addRoute($name, $route) {
		$this->_routes [$name] = $route;
		
		return $this;
	}
	
	/**
	 * Add routes to the route chain
	 *
	 * @param array $routes
	 *        	Array of routes with names as keys and routes as values
	 * @return \Core\Router\Router
	 */
	public function addRoutes($routes) {
		foreach ( $routes as $name => $route ) {
			$this->addRoute ( $name, $route );
		}
		
		return $this;
	}
	public function match($path, $partial = false) {
		$match = false;
		foreach ( $this->_routes as $name => $route ) {
			if ( ($match = $route->match ( $path, $partial )) !== false ) {
				$this->_currentRoute = $name;
				$this->_defaults = $route->getDefaults ();
// 				return $route->match ( $path, $partial );
				//fix: if is Dispatchable return
				$checkFix = array_merge([
					'module' 		=> $this->frontController->getDefaultModule(),
					'controller' 	=> $this->frontController->getDefaultController(),
					'action' 		=> $this->frontController->getDefaultAction()
				], $match);
				if($this->isDispatchable($checkFix['controller'], $checkFix['module'], $checkFix['action'])) {
					return $match;
				}
			}
		}
		return $match;
	}
	
	public function isDispatchable($controller, $module, $action) {
		$className = $this->frontController->formatControllerName ( $controller );
		$classModuleName = $this->frontController->formatControllerName ( $module . '\\' . $controller );
		$actionName = $this->frontController->formatActionName($action);
		if (class_exists ( $classModuleName, false )) {
			return method_exists($classModuleName, $actionName);
		}
		
		try {
			$fileSpec = $this->frontController->classToFilename ( $className );
			$dispatchDir = $this->frontController->getModuleDirectoryWithDefault ($module) . DIRECTORY_SEPARATOR . 'controllers';
			$test = $dispatchDir . DIRECTORY_SEPARATOR . $fileSpec;
			if(\Core\Loader\Loader::isReadable ( $test )) {
				return method_exists($classModuleName, $actionName);
			}
		} catch (\Core\Exception $e) {}
		return false;
	}
	
	/**
	 * Remove a route from the route chain
	 *
	 * @param string $name
	 *        	Name of the route
	 * @throws \Core\Exception
	 * @return \Core\Router\Rewrite
	 */
	public function removeRoute($name) {
		if (! isset ( $this->_routes [$name] )) {
			throw new \Core\Exception ( "Route $name is not defined" );
		}
		
		unset ( $this->_routes [$name] );
		
		return $this;
	}
	
	/**
	 * Check if named route exists
	 *
	 * @param string $name
	 *        	Name of the route
	 * @return boolean
	 */
	public function hasRoute($name) {
		return isset ( $this->_routes [$name] );
	}
	
	/**
	 * Retrieve a named route
	 *
	 * @param string $name
	 *        	Name of the route
	 * @throws \Core\Exception
	 * @return \Core\Router\Regex
	 */
	public function getRoute($name) {
		if (! isset ( $this->_routes [$name] )) {
			throw new \Core\Exception ( "Route $name is not defined" );
		}
		
		return $this->_routes [$name];
	}
	
	/**
	 * Retrieve an array of routes added to the route chain
	 *
	 * @return array All of the defined routes
	 */
	public function getRoutes() {
		return $this->_routes;
	}
	
	/**
	 * Generates a URL path that can be used in URL creation, redirection, etc.
	 *
	 * @param array $userParams
	 *        	Options passed by a user used to override parameters
	 * @param mixed $name
	 *        	The name of a Route to use
	 * @param bool $reset
	 *        	Whether to reset to the route defaults ignoring URL params
	 * @param bool $encode
	 *        	Tells to encode URL parts on output
	 * @throws \Core\Exception
	 * @return string Resulting absolute URL path
	 */
	public function assemble($userParams, $name = null, $reset = false, $encode = true) {
		if ($name == null) {
			try {
				$name = $this->getCurrentRouteName ();
			} catch ( \Core\Exception $e ) {
				$name = 'default';
			}
		}
		
		$params = array_merge ( $this->_globalParams, $userParams );
		
		$route = $this->getRoute ( $name );
		$url = $route->assemble ( $params, $reset, $encode );
		
		if (! preg_match ( '|^[a-z]+://|', $url )) {
			$url = rtrim ( \Core\Http\Request::getInstance ()->getBaseUrl (), '/' ) . '/' . $url;
		}
		
		return $url;
	}
	
	/**
	 *
	 * @return string Resulting Route name
	 */
	public function getCurrentRouteName() {
		return $this->_currentRoute;
	}
	
	/**
	 * Set a global parameter
	 *
	 * @param string $name        	
	 * @param mixed $value        	
	 * @return
	 *
	 */
	public function setGlobalParam($name, $value) {
		$this->_globalParams [$name] = $value;
		
		return $this;
	}
	
	/**
	 * Return a single parameter of route's defaults
	 *
	 * @param string $name
	 *        	Array key of the parameter
	 * @return string Previously set default
	 */
	public function getDefault($name) {
		if (isset ( $this->_defaults [$name] )) {
			return $this->_defaults [$name];
		}
	}
	
	/**
	 * Return an array of defaults
	 *
	 * @return array Route defaults
	 */
	public function getDefaults() {
		return $this->_defaults;
	}
}
 

///////////////////////////////////////////////////////
//
//
//$route = new Router_Route_Regex(
//    'blog/archive/(\d+)-(.+)\.html',
//    array(
//        'controller' => 'blog',
//        'action'     => 'view'
//    ),
//    array(
//        1 => 'id',
//        2 => 'description'
//    ),
//    'blog/archive/%d-%s.html'
//);
//
//$test = new Router_Rewrite;
//
//$test->addRoute('default', $route);
//
//$test->addRoute('test', new Router_Route_Regex(
//    'admin/(\w+)?/?(\w+)?/?',
//    array(
//        'controller' => 'index',
//        'action'     => 'index',
//	'modul'	     => 'admin'
//    ),
//    array(
//        1 => 'controller',
//        2 => 'action'
//    ),
//    'admin/%s'
//));
//
//$data = array(
//'controller' => 'blog',
//'action'     => 'view',
//'id' => 1,
//'description'=>'test'
//);
//
//var_dump($test->match('admin/test/', true));
//
////var_dump($test->assemble($data, $reset = false, $encode = false, $partial = false));