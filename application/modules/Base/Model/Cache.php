<?php

namespace Base\Model;

class Cache extends \Base\Model\Reference {
	
	public static function set($key, $data, $livetime = 600) {
		self::remove($key);
		$serialized = array(
			'data' => $data,
			'expire' => \Core\Date::getInstance(null,\Core\Date::SQL_FULL,true)->setInterval('+' . $livetime . ' seconds')->dateToUnix()	
		);
		$self = new self();
		$new = $self->fetchNew();
		$new->key = $key;
		$new->data = serialize($serialized);
		try {
			return $new->save();
		} catch (\Core\Exception $e) {
			return false;
		}
	}
	
	public static function get($key) {
		$self = new self();
		$result = $self->fetchRow($self->makeWhere(array('key'=>$key)));
		if($result) {
			$result = @unserialize($result->data);
			if($result) {
				if($result['expire'] > time()) {
					return $result['data'];
				}
				self::remove($key);
			}
		}
		return false;
	}
	
	public static function remove($key) {
		$self = new self();
		return $self->delete($self->makeWhere(array('key'=>$key)));
	}
	
}