<?php

namespace Pin\Helper;

class Ext {

	public static function parse($pin, $withKey = true) {
		//labels and other
		$data = array();
		foreach($pin AS $k=>$v) {
			if(strpos($k, 'extended_pin_') === 0 && $v) {
				$object = new $v($pin, $withKey);
				if($object instanceof \Pin\Helper\Format) {
					if( is_array($result = $object->result()) ) {
						$data = \Core\Arrays::array_merge($data, $result);
					}
				}
			}
		}
		return $data;
		//labels and other
	}
	
}