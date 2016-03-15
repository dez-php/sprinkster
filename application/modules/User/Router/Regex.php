<?php

namespace User\Router;

class Regex extends \Core\Router\Regex {
	
	private static $cache = [];
	protected $regexp;
	
	public function assemble(array $data = array(), $reset = false, $encode = false, $partial = false) {
		$key = md5(serialize([func_get_args(), $this]));
		if(!isset($data['user_id']) || (isset(self::$cache[$key]) && is_null(self::$cache[$key])))
			return parent::assemble($data, $reset, $encode, $partial);
		if(isset(self::$cache[$key]) && self::$cache[$key])
			return self::$cache[$key];
		if(isset($data['query']) && $data['query']) {
			$user = (object)['username'=>$data['query']];
		} else if(is_null($user = (new \User\User())->fetchRow(['id = ?' => $data['user_id']])) || !$user->username) {
			self::$cache[$key] = null;
			return parent::assemble($data, $reset, $encode, $partial);
		}
		
		$data = array_merge($data, ['user_id'=>$user->username, 'query' => '']);
		$replaces = array ();
		foreach ( $this->_map as $map ) {
			if (isset ( $data [$map] )) {
				$replaces [$map] = $encode ? urlencode ( $data [$map] ) : $data [$map];
			} else if (isset ( $this->_defaults [$map] )) {
				$replaces [$map] = $this->_defaults [$map];
			} else if ($this->_request->getRequest ( $map )) {
				$replaces [$map] = $this->_request->getRequest ( $map );
			}
		}
		
		$return = self::$cache[$key] = @vsprintf ( $this->_reverse, $replaces );
		
		if ($return === false) {
			throw new \Core\Exception ( 'Cannot assemble. Too few arguments?' );
		}
		
		return rtrim($return, '/');
	}
	
	public function match($path, $partial = false) {
		
		if(!$this->regexp)
			return parent::match($path, $partial);
		
		if (! $partial) {
			$path = $path != '/' ? rtrim ( urldecode ( $path ), '/' ) : $path;
			$regex = '#^' . $this->regexp . '$#i';
		} else {
			$regex = '#^' . $this->regexp . '#i';
		}
		
		$res = preg_match ( $regex, $path, $values );
		if ($res === 0 || !isset($values['username']) || !trim($values['username']))
			return parent::match($path, $partial);
		
		if(is_null($user = (new \User\User())->fetchRow(['username LIKE ?' => $values['username']])))
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
			'user_id' => $user->id,
			'query' => $user->username
		]);
		
	}
	
}