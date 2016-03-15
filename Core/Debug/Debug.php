<?php

namespace Core\Debug;

defined('DEBUG_TRACE_LEVEL') || define('DEBUG_TRACE_LEVEL', 2);
include_once 'HelperLoger.php';

class Debug {

	protected static $debuging = false;
	protected static $begin_time;
	protected static $_logger;
	private static $_instance;
	
	/**
	 * @param array $options
	 * @return \FM\Http\Request
	 */
	public static function getInstance() {
		if(self::$_instance == null) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	public function __construct() {
		self::$begin_time = microtime(true);
	}
	
	public static function getDebug() {
		return self::$debuging;
	}
	
	public static function setDebug($debug) {
		self::getInstance();
		self::$debuging = $debug;
		return self::$_instance;
	}
	
	public static function getBeginTime() {
		self::getInstance();
		return self::$begin_time;
	}
	
	public static function trace($msg,$category='application') {
		if(self::getDebug()) {
			self::log($msg,HelperLoger::LEVEL_TRACE,$category);
		}
	}
	public static function log($msg,$level=HelperLoger::LEVEL_INFO,$category='application') {
		self::getInstance();
		if(self::$_logger===null)
			self::$_logger=self::getLogger();
		if(self::getDebug() && DEBUG_TRACE_LEVEL>0 && $level!==HelperLoger::LEVEL_PROFILE)
		{
			$traces=debug_backtrace();
			$count=0;
			foreach($traces as $trace)
			{
				if(isset($trace['file'],$trace['line']) && strpos($trace['file'],\Core\Base\Init::getBase())!==0)
				{
					$msg.="\nin ".$trace['file'].' ('.$trace['line'].')';
					if(++$count>=DEBUG_TRACE_LEVEL)
						break;
				}
			}
		}
		self::$_logger->log($msg,$level,$category);
	}
	public static function beginProfile($token,$category='application')
	{
		self::log('begin:'.$token,HelperLoger::LEVEL_PROFILE,$category);
	}
	
	public static function endProfile($token,$category='application')
	{
		self::log('end:'.$token,HelperLoger::LEVEL_PROFILE,$category);
	}
	
	/**
	 * @return \Core\Debug\HelperLoger
	 */
	public static function getLogger()
	{
		if(self::$_logger!==null)
			return self::$_logger;
		else
			return self::$_logger=new HelperLoger;
	}
	
}