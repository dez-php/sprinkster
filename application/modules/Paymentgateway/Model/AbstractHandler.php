<?php
namespace Paymentgateway;

abstract class AbstractHandler {

	protected $provider = NULL;
	protected $metadata = [];

	/**
	 * Method for detection of handler by HTTP request
	 * @return bool TRUE if the handler is meant to deal with the request, FALSE otherwise
	 */
	public abstract function detect();

	public function setProvider(&$provider)
	{
		$this->provider = $provider;
	}

	public function meta($key, $value = NULL)
	{
		if(1 == func_num_args())
			return isset($this->metadata[$key]) ? $this->metadata[$key] : NULL;
		
		$this->metadata[$key] = $value;
	}

}