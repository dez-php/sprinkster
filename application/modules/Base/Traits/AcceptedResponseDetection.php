<?php

namespace Base\Traits;

use Core\Http\Request;

trait AcceptedResponseDetection
{
	protected static $ResponseFormatUnknown = 'any';
	protected static $ResponseFormatPlain = 'html';
	protected static $ResponseFormatJson = 'json';

	protected static $mimes = [
		'any' => [
			'*/*',
			'application/octet-stream',
		],
		'html' => [
			'text/html',
			'application/xhtml+xml',
			'application/xml',
		],

		'json' => [
			'application/json',
			'text/javascript',
			'application/javascript',
			'application/ecmascript',
			'application/x-ecmascript',
		],
	];

	public static function accept($request)
	{
		if(!$request || !is_object($request) || !($request instanceof Request))
			return [];

		$accept = explode(',', explode(';', $request->getHeader('Accept'))[0]);
		array_walk($accept, 'trim');

		return $accept;
	}

	public static function detect($request, $prefer = NULL)
	{
		$accepted = self::accept($request);
		$signature = 0;
		$accept = [];

		// Collect possible negotiated acceptions
		foreach(self::$mimes as $id => $possibilities)
		{
			foreach($accepted as $mime)
			{
				if(in_array($mime, $possibilities))
					$accept[] = $id;
			}
		}

		$accept = array_unique($accept);
		$accept = array_filter($accept);

		// Not recognized
		if(empty($accept))
			return self::$ResponseFormatUnknown;

		// Get the first match if only 1, no prefs or preference invalid
		if(2 > count($accept) || !$prefer || !in_array($prefer, $accept))
			return $accept[0];

		return $prefer;
	}

}