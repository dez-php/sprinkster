<?php

namespace Core;

class Camel {
	
	/**
	 * Translates a camel case string into a string with underscores (e.g.
	 * firstName -&gt; first_name)
	 *
	 * @param string $str
	 *        	String in camel case format
	 * @return string $str Translated into underscore format
	 */
	public static function fromCamelCaseOld($str) {
		$str [0] = strtolower ( $str [0] );
		$func = create_function ( '$c', 'return "_" . strtolower($c[1]);' );
		return preg_replace_callback ( '/([A-Z])/', $func, $str );
	}
	
	/**
	 * Translates a string with underscores into camel case (e.g.
	 * first_name -&gt; firstName)
	 *
	 * @param string $str
	 *        	String in underscore format
	 * @param bool $capitalise_first_char
	 *        	If true, capitalise the first char in $str
	 * @return string $str translated into camel caps
	 */
	public static function toCamelCaseOld($str, $capitalise_first_char = false, $with_space = false) {
		if ($capitalise_first_char) {
			$str [0] = strtoupper ( $str [0] );
		}
		$func = create_function ( '$c', 'return ' . ($with_space ? '" ".' : '') . 'strtoupper($c[1]);' );
		return preg_replace_callback ( '/_([a-z])/', $func, $str );
	}

	/**
	 * Translates a camel case string into a string with underscores (e.g.
	 * firstName -&gt; first_name)
	 *
	 * @param string $str
	 *        	String in camel case format
	 * @return string $str Translated into underscore format
	 */
	public static function fromCamelCase($str)
	{
		return implode('_', array_map('strtolower', preg_split('/(?<=\\w)(?=[A-Z])/', $str)));
	}

	/**
	 * Translates a string with underscores into camel case (e.g.
	 * first_name -&gt; firstName)
	 *
	 * @param string $str
	 *        	String in underscore format
	 * @param bool $capitalise_first_char
	 *        	If true, capitalise the first char in $str
	 * @return string $str translated into camel caps
	 */
	public static function toCamelCase($str, $capitalise_first_char = false, $with_space = false)
	{
		$str = ucwords(str_replace('_', ' ', $str));

		if(!$with_space)
			$str = str_replace(' ', '', $str);

		if (!$capitalise_first_char)
			$str[0] = strtolower($str[0]);

		return $str;
	}
}

?>