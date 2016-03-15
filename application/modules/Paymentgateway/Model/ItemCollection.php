<?php
namespace Paymentgateway;

use \ArrayIterator;
use \IteratorAggregate;

use \Core\Base\Core;

class ItemCollection extends Core implements IteratorAggregate {

	protected $items = [];
	protected $map = [];

	private function is_valid($item)
	{
		return $item->is('Paymentgateway\Item') && $item->is_valid();
	}

	public function items()
	{
		return $this->items;
	}

	public function getIterator()
	{
		return new ArrayIterator($this->items);
	}

	public function add($item)
	{
		if(!$this->is_valid($item))
			return;

		if($this->exists($item))
			return;

		$this->items[] = $item;
		$this->map[$item->UID()] = count($this->items) - 1;
	}

	public function remove($item)
	{
		if(!$this->exists($item))
			return;

		$index = $this->map[$item->UID()];

		unset($this->items[$index], $this->map[$item->UID()]);

		$this->items = array_values($this->items);
	}

	public function exists($item)
	{
		return isset($this->map[$item->UID()]);
	}

	public function count()
	{
		return count($this->items);
	}

	public function is_empty()
	{
		return 0 === count($this->items);
	}

	public function merge($collection)
	{
		foreach($collection->items as $item)
		{
			if($this->exists($item))
				return;

			$this->items[] = $item;
			$this->map[$item->UID()] = count($this->items) - 1;
		}
	}

	/**
	 * @param int $index
	 * @return \Paymentgateway\Item
	 */
	public function at($index)
	{
		return isset($this->items[$index]) ? $this->items[$index] : NULL;
	}

	public function indexOf($item)
	{
		if(!is_object($item) || !$item->is('Paymentgateway\Item'))
			return FALSE;

		$this->map[ $item->UID() ];
	}

	public function json()
	{
		return \Core\Encoders\Json::encode($this->items);
	}

}