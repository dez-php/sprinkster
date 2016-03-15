<?php
namespace Cart;

use \Paymentgateway\Item;

abstract class SubscribeItem extends Item {

	protected $period = NULL;
	protected $length = 0;
	protected $parent;

	public function __construct($item)
	{
		if(!$item)
			throw new \Core\Exception('Invalid Item');

		$this->id = $item->id;
		$this->module = $this->getCurrentModule();
		$this->record = $item;
		$this->name = $item->title;
		$this->description = $item->description;
		$this->price = round($item->price, 2);
		$this->currency = \Currency\Helper\Format::getCurrencyCode();
		$this->period = $item->period;
		$this->length = (int) $item->length;
		$this->qty = 1;
		$this->parent = isset($item->parent) ? $item->parent : null;
	}

	public function __get($name)
	{
		if(property_exists($this, $name) || isset($this->$name))
			return $this->$name;
	}

	public function getStockQty()
	{
		return 1;
	}
	
	public function getPeriod() {
		return $this->period;
	}
	
	public function getLength() {
		return $this->length;
	}
	
	public function getParent() {
		return $this->parent;
	}
	
	public function getRecord() {
		return isset($this->data['record']) ? $this->data['record'] : null;
	}

	public function withdraw()
	{
		throw new \Core\Exception('NotImplementedException');
	}

	public function restore()
	{
		throw new \Core\Exception('NotImplementedException');
	}

}