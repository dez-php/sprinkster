<?php
namespace Paymentgateway;

use \Core\Base\Core;

abstract class Item extends Core implements IPurchasable {

	const UID_SEPARATOR = ':';

	protected $id;
	protected $module;
	protected $reflection;
	protected $name;
	protected $description;
	protected $price;
	protected $currency;
	protected $qty;

	/**
	 * Method implementation of IPurchasable for retrieving item unit price
	 * @return float Returns the price value of a single item unit
	 */
	public function getUnitValue()
	{
		return $this->price;
	}

	/**
	 * Method implementation of IPurchasable for retrieving item(s) value for the quantity given
	 * @return float Returns the price value of a item
	 */
	public function getValue()
	{
		return $this->price * $this->qty;
	}

	/**
	 * Check if item is in valid system state
	 * @return bool Returns true if the item object is consistent, false otherwise
	 */
	public function is_valid()
	{
		if($this->getStockQty() < $this->qty)
			return FALSE;

		return TRUE;
	}

	/**
	 * Get current instance module
	 * @return string Module ID
	 */
	public function getCurrentModule() {
		return explode(NS, get_class($this))[0];
	}

	public function UID()
	{
		return $this->module . self::UID_SEPARATOR . $this->ID;
	}

	public function switchCurrency($currency)
	{
		$test = TRUE;

		$this->price = \Currency\Helper\Format::convert((float) $this->price, $this->currency, $currency, $test);

		if(!$test)
			throw new Exception(Exception::ConversionFailed);

		$this->currency = $currency;
	}

	/**
	 * Returns stock quantity available for this item
	 * @return mixed The amount in stock 
	 */
	public abstract function getStockQty();

	/**
	 * Withdraw items from stock (pull out)
	 * @return bool Whether the the items are withdrawn successfully
	 */
	public abstract function withdraw();

	/**
	 * Restore items in stock (load in stock)
	 * @return bool Whether the quantity is put back in the stock
	 */
	public abstract function restore();


}