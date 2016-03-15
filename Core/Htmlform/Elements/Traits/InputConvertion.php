<?php

namespace Core\Htmlform\Elements\Traits;

use \Travesable;
use \Iterator;

trait InputConvertion
{
	protected static $show_empty = FALSE;
	protected static $empty_value = '';
	protected static $empty_text = '';

	public static function setEmptyItem($show, $text = '', $value = '')
	{
		self::$show_empty = (bool) $show;
		self::$empty_value = $value;
		self::$empty_text = $text;
	}

	/**
	 * Converts an DB result array to expected format for Movable
	 * @param  array $source     Source array with original values
	 * @param  string $text_key  Key in source array items, corresponding to visual part of the option
	 * @param  string $value_key Key in source array items, corresponding to value part of the option
	 * @return array             Well formed array for Movable or empty one if corrupted data passed
	 */
	public static function convert($source, $text_key = 'name', $value_key = 'id')
	{
		if(!$source || (!is_array($source) && !($source instanceof Traversable) && !($source instanceof Iterator)) || empty($source))
			return [];

		$result = [];

		if(self::$show_empty)
			$result[self::$empty_value] = self::$empty_text;

		foreach($source as $option)
		{
			if(is_array($option) && isset($option[$value_key]) && isset($option[$text_key]))
				$result[$option[$value_key]] = $option[$text_key];

			if(is_object($option) && isset($option->$value_key) && isset($option->$text_key))
				$result[$option->$value_key] = $option->$text_key;
		}

		return $result;
	}

	/**
	 * Converts selection to proper format for enabled values, where 1D array is required with the selected IDs
	 * @param  array $source     Source array with data
	 * @param  string $value_key The name of the key corresponding to selection values
	 * @return array             An array with the extracted values - 1D
	 */
	public static function convertSelection($source, $value_key = 'id')
	{
		if(!$source || (!is_array($source) && !($source instanceof Traversable) && !($source instanceof Iterator)) || empty($source))
			return [];

		$result = [];

		foreach($source as $option)
		{
			if(is_array($option) && isset($option[$value_key]))
				$result[] = $option[$value_key];

			if(is_object($option) && isset($option->$value_key))
				$result[] = $option->$value_key;
		}

		return $result;
	}

}