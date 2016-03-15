<?php

namespace Currency;

class Currency extends \Base\Model\Reference {

	public static function findPrice($text) {
		$table = new self();
		$currency = $table->fetchAll(array('status = 1'));
		$currency_left = $currency_right = $currency_data = array();
		foreach($currency AS $c) {
			$RegExp  = '(?P<price_' . $c->code . '>([\d' . preg_quote($c->thousand_point) . ']{1,})';
			if($c->decimal_place) {
				$RegExp .= '(' . preg_quote($c->decimal_point) . '(?P<decimal_' . $c->code . '>[\d]{' . $c->decimal_place . '}))?';
			}
			$RegExp .= ')';
			if($c->symbol_left) {
				$currency_left[$c->code] = '((?P<simbol_' . $c->code . '>' . preg_quote(trim($c->symbol_left)) . ')\s?' . $RegExp . ')';
			} else if($c->symbol_right) {
				$currency_right[$c->code] = '(' . $RegExp . '\s?(?P<simbol_' . $c->code . '>' . preg_quote(trim($c->symbol_right)) . '))';
			}
			$currency_data[$c->code] = $c;
		}

		if($currency_left) {
			if(preg_match('~(' . implode('|',$currency_left) . ')~i', $text, $match)) {
				foreach($match AS $k=>$m) {
					if(strpos($k, 'price_') !== false && $m) {
						$std = new \stdClass();
						$std->price = str_replace($currency_data[substr($k, 6)]->decimal_point, '.', str_replace($currency_data[substr($k, 6)]->thousand_point, '', $m));
						$std->code = substr($k, 6);
						return $std;
					}
				}
			}
		} 
		if($currency_right) {
			if(preg_match('~(' . implode('|',$currency_right) . ')~i', $text, $match)) {
				foreach($match AS $k=>$m) {
					if(strpos($k, 'price_') !== false && $m) {
						$std = new \stdClass();
						$std->price = str_replace($currency_data[substr($k, 6)]->decimal_point, '.', str_replace($currency_data[substr($k, 6)]->thousand_point, '', $m));
						$std->code = substr($k, 6);
						return $std;
					}
				}
			}
		}
		return null;
	}
	
}
//?<' . $c->code . '>