<?php

namespace Page\Router;

class Regex extends \Core\Router\Regex {

	private static $cache = [];
	public static $cacheSelect = [];
	
	public function assemble(array $data = array(), $reset = false, $encode = false, $partial = false) {
		if(!isset($data['page_id']) || (isset(self::$cache[$data['page_id']]) && is_null(self::$cache[$data['page_id']])))
			return parent::assemble($data, $reset, $encode, $partial);
		if(isset(self::$cache[$data['page_id']]) && self::$cache[$data['page_id']])
			return self::$cache[$data['page_id']];
		if(isset(\Page\Router\Regex::$cacheSelect[$data['page_id']])) {
			if(!\Page\Router\Regex::$cacheSelect[$data['page_id']]) {
				self::$cache[$data['page_id']] = null;
				return parent::assemble($data, $reset, $encode, $partial);
			}
			$page = (object)['key' => \Page\Router\Regex::$cacheSelect[$data['page_id']]];
		} else if(is_null($page = (new \Page\Page())->get($data['page_id'])) || !$page->key) {
			self::$cache[$data['page_id']] = null;
			return parent::assemble($data, $reset, $encode, $partial);
		}
		
		$return = self::$cache[$data['page_id']] = @vsprintf ( $this->_reverse, [$page->key, ''] );
		
		if ($return === false) {
			throw new \Core\Exception ( 'Cannot assemble. Too few arguments?' );
		}
		
		return $return;
	}
	
	public function match($path, $partial = false) {
		if (! $partial) {
			$path = $path != '/' ? rtrim ( urldecode ( $path ), '/' ) : $path;
			$regex = '#^page/([^/]{1,})/?$#i';
		} else {
			$regex = '#^page/([^/]{1,})/?#i';
		}
		
		$res = preg_match ( $regex, $path, $values );
		if ($res === 0)
			return parent::match($path, $partial);
		
		if(is_null($page = (new \Page\Page())->getByKey($values[1])))
			return false;
		
		if ($partial) {
			$this->setMatchedPath ( $values [0] );
		}
		
		// array_filter_key()? Why isn't this in a standard PHP function set
		// yet? :)
		foreach ( $values as $i => $value ) {
			if (! is_int ( $i ) || $i === 0) {
				unset ( $values [$i] );
			}
		}
		
		$this->_values = $values;
		
		$values = $this->_getMappedValues ( $values );
		$defaults = $this->_getMappedValues ( $this->_defaults, false, true );
		$return = $values + $defaults;

		return array_merge($return, [
			'page_id' => $page->id,
			'query' => $page->key
		]);
		
	}
	
}