<?php
namespace Core\Http;

/**
 * Class that supplies helper methods for work with URI resources
 * @name URL
 * @package Core
 * @subpackage Http
 * @version 1.0
 * @license Commercial
 */
class Url {

	const NONE = '';
	const HTTP = 'http://';
	const HTTPS = 'https://';
	const WS = 'ws://';
	const WSS = 'wss://';
	const FILE = 'file:///';

	const STATUS_OK = 200;
	const STATUS_REDIRECT = 301;

	private static $request = NULL;
	private static $base_url = NULL;

	/**
	 * Method that builds valid URL
	 * @param  string $url      The desired URI to be built
	 * @param  string $protocol The protocol for the URI address
	 * @return string           Valid URL
	 */
	public static function build($url, $protocol = self::HTTP)
	{
		$url = preg_replace("#((?<!:)/+|\\+)#", '/', $url);
		$url = preg_replace('#^([a-z]+://+)+#i', '', $url);

		//if($protocol && !preg_match('#^' . $protocol . '://#', $url))
		$url = $protocol . $url;

		return $url;
	}


	/**
	 * Builds in-system URL to the desired resource
	 * @param  string $url URI address to be built
	 * @return string      The in-system URL with the given path
	 */
	public static function to($url = NULL)
	{
		if(!self::$request)
		{
			self::$request = \Core\Http\Request::getInstance();
			self::$base_url = rtrim(self::$request->getBaseUrl(), '/\\ ');
		}

		return self::$base_url . '/' . trim($url, '/\\ ');
	}

	/**
	 * Method that maks a ping of a given URL
	 * @param  string $url The URI to be pinged
	 * @return bool        True if URI is reachable, false otherwise
	 */
	public static function ping($url)
	{
		$ch = curl_init($url);
		
		if(!$ch)
			return FALSE;
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		
		curl_exec($ch);
		
		if(curl_errno($ch))
			return FALSE;
		
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
		curl_close($ch);
		
		return self::STATUS_OK === $status;
	}

}