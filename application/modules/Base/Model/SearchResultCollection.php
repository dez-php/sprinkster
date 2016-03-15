<?php

namespace Base\Model;

class SearchResultCollection {

	protected $items = [];
	protected $map = [];

	private function is_valid($item)
	{
		return $item->is('Base\Model\SearchResult') && $item->is_valid();
	}

	public function items()
	{
		return $this->items;
	}

	public function add($item)
	{
		if(!$this->is_valid($item))
			return;
	//		throw new \Core\Exception('Search result item is invalid.');

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

	public function json()
	{
		return \Core\Encoders\Json::encode($this->items);
	}

}