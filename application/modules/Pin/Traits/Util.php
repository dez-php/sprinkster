<?php

namespace Pin\Traits;

trait Util
{
	public function getProductNumber(\Core\Db\Select $sql) {
		$where = $sql->getPart(\Core\Db\Select::WHERE);
		$product = null;
		if($where) {
			foreach($where AS $w) {
				$w = str_replace('`', '', $w);
				if(preg_match('~(pin.)?product\s?=\s?([\d]{1,})~', $w, $m)) {
					$product = $m[2];
					break;
				}
			}
		}
		return $product;
	}
}