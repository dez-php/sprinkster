<?php

namespace Base\Model;

class SearchProviderContainer {

	protected static $providers = [];

	public static function register($provider)
	{
		if(!$provider->is('Base\Model\SearchProvider'))
			throw new \Core\Exception('Registration of a search provider failed. The provider is not an instance of SearchProvider.');

		self::$providers[$provider->ID()] = $provider;
	}

	public static function unregister($ID)
	{
		if(!isset(self::$providers[$ID]))
			return;

		unset(self::$providers[$ID]);
	}
	
	public static function query($query, $_ = NULL)
	{
		if(!is_array(self::$providers))
			throw new \Core\Exception('Providers are corrupted.');

		if(empty(self::$providers))
			return NULL;

		$result = new \Base\Model\SearchResultCollection;

		foreach(self::$providers as $provider)
			$result->merge($provider->query($query, $_));

		return $result;
	}

}