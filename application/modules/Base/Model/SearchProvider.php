<?php

namespace Base\Model;

abstract class SearchProvider extends \Core\Base\Core {

	protected $ID = 'SearchProviderID';

	const DefaultFeedLimit = 20;

	public function __construct()
	{
	}

	public function ID()
	{
		return $this->ID;
	}

	public function register()
	{
		SearchProviderContainer::register($this);
	}

	public function unregister()
	{
		SearchProviderContainer::unregister($this->ID);
	}

	public abstract function query($query, $translate = NULL);

}