<?php

namespace Core;

class Arrays {
	
	/**
	 * Return the first element in an array which passes a given truth test.
	 *
	 * <code>
	 * // Return the first array element that equals "Taylor"
	 * $value = array_first($array, function($k, $v) {return $v == 'Taylor';});
	 *
	 * // Return a default value if no matching element is found
	 * $value = array_first($array, function($k, $v) {return $v == 'Taylor'},
	 * 'Default');
	 * </code>
	 *
	 * @param array $array        	
	 * @param Closure $callback        	
	 * @param mixed $default        	
	 * @return mixed
	 */
	public static function array_first($array, $callback, $default = null) {
		foreach ( $array as $key => $value ) {
			if (call_user_func ( $callback, $key, $value ))
				return $value;
		}
		
		return self::value ( $default );
	}
	
	/**
	 * Return the value of the given item.
	 *
	 * If the given item is a Closure the result of the Closure will be
	 * returned.
	 *
	 * @param mixed $value        	
	 * @return mixed
	 */
	public static function value($value) {
		return (is_callable ( $value ) and ! is_string ( $value )) ? call_user_func ( $value ) : $value;
	}
	
	/**
	 * Get an item from an array using "dot" notation.
	 *
	 * <code>
	 * // Get the $array['user']['name'] value from the array
	 * $name = array_get($array, 'user.name');
	 *
	 * // Return a default from if the specified item doesn't exist
	 * $name = array_get($array, 'user.name', 'Taylor');
	 * </code>
	 *
	 * @param array $array        	
	 * @param string $key        	
	 * @param mixed $default        	
	 * @return mixed
	 */
	public static function array_get($array, $key, $default = null) {
		if (is_null ( $key ))
			return $array;
			
			// To retrieve the array item using dot syntax, we'll iterate
		// through
			// each segment in the key and look for that value. If it exists, we
			// will return it, otherwise we will set the depth of the array and
			// look for the next segment.
		foreach ( explode ( '.', $key ) as $segment ) {
			if (! is_array ( $array ) or ! array_key_exists ( $segment, $array )) {
				return self::value ( $default );
			}
			
			$array = $array [$segment];
		}
		
		return $array;
	}
	
	/**
	 * Merge one or more arrays
	 * 
	 * @param
	 *        	array1 array <p>
	 *        	Initial array to merge.
	 *        	</p>
	 * @param
	 *        	array2 array[optional]
	 * @param
	 *        	_ array[optional]
	 * @return array the resulting array.
	 */
	public static function array_merge(array $array1, array $array2 = null) {
		$args = func_get_args ();
		if (count ( $args ) < 2) {
			return $array1;
		}
		for($i = 1; $i < count ( $args ); $i ++) {
			if (is_array ( $args [$i] )) {
				foreach ( $args [$i] as $key => $val ) {
					if (is_array ( $args [$i] [$key] )) {
						$array1 [$key] = (array_key_exists ( $key, $array1 ) && is_array ( $array1 [$key] )) ? self::array_merge ( $array1 [$key], $args [$i] [$key] ) : $args [$i] [$key];
					} else {
						$array1 [$key] = $val;
					}
				}
			}
		}
		return $array1;
	}
	
	/**
	 * Merge one or more arrays
	 * 
	 * @param
	 *        	array1 array <p>
	 *        	Initial array to merge.
	 *        	</p>
	 * @param
	 *        	array2 array[optional]
	 * @param
	 *        	_ array[optional]
	 * @return array the resulting array.
	 */
	public static function array_merge_with_key_increment(array $array1, array $array2 = null) {
		$args = func_get_args ();
		if (count ( $args ) < 2) {
			return $array1;
		}
		for($i = 1; $i < count ( $args ); $i ++) {
			if (is_array ( $args [$i] )) {
				$t=0;
				foreach ( $args [$i] as $key => $val ) {
					if (is_array ( $args [$i] [$key] )) {
						$array1 [$key.'.'.$t++] = (array_key_exists ( $key, $array1 ) && is_array ( $array1 [$key] )) ? self::array_merge_with_key_increment ( $array1 [$key], $args [$i] [$key] ) : $args [$i] [$key];
					} else {
						$array1 [$key.'.'.$t++] = $val;
					}
				}
			}
		}
		return $array1;
	}
	
	public static function array_change_value_case($input, $case = CASE_LOWER) {
		$aRet = array ();
		
		if (! is_array ( $input )) {
			return $aRet;
		}
		
		foreach ( $input as $key => $value ) {
			if (is_array ( $value )) {
				$aRet [$key] = self::array_change_value_case ( $value, $case );
				continue;
			}
			
			$aRet [$key] = ($case == CASE_UPPER ? mb_strtoupper ( $value, 'utf-8' ) : mb_strtolower ( $value, 'utf-8' ));
		}
		
		return $aRet;
	}
}