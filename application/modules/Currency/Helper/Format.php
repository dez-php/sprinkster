<?php

namespace Currency\Helper;

class Format {
	protected static $code;
	protected static $currencies = array ();
	
	public static function setCode($default = null) {
		$default = $default && self::hasCurrency($default) ? $default : \Base\Config::get ( 'config_currency' );
		$action = \Core\Base\Action::getInstance();
		if($action->isModuleAccessible('Multicurrency')) {
			$currency = $action->getModule('Multicurrency')->getCurrency();
			if(self::hasCurrency($currency))
				return $currency;
		}
		return $default;
	}
	
	public static function convert($number, $from, $to = '', &$converted = null) {
		self::construct ();
		if(self::hasCurrency ( $from ) && self::hasCurrency ( $to )) {
			if($from == $to) {
				$converted = false;
				return $number;
			} else {
				$from_value = self::$currencies [$from] ['value'];
				$to_value = self::$currencies [$to] ['value'];
				$decimal_place = self::$currencies [$to] ['decimal_place'];
				$converted = true;
				
				return round(($number/$from_value)*$to_value, $decimal_place, PHP_ROUND_HALF_UP);
			}
		}
		$converted = false;
		return $number;
	}
	
	public static function format_convert($number, $from, $to, &$converted = null) {
		$from = $from && self::hasCurrency ( $from ) ? $from : self::$code;
		$to = $to && self::hasCurrency ( $to ) ? $to : self::$code;
		$number = self::convert($number, $from, $to, $converted);
		return self::format($number, $to, 1);
	}
	
	public static function format($number, $currency = '', $value = '', $format = true, $decimal_place_set = false) {
		self::construct ();
		if ($currency && self::hasCurrency ( $currency )) {
			$symbol_left = self::$currencies [$currency] ['symbol_left'];
			$symbol_right = self::$currencies [$currency] ['symbol_right'];
			$decimal_place = self::$currencies [$currency] ['decimal_place'];
			$decimal_point = self::$currencies [$currency] ['decimal_point'];
			$thousand_point = self::$currencies [$currency] ['thousand_point'];
		} else {
			$symbol_left = self::$currencies [self::$code] ['symbol_left'];
			$symbol_right = self::$currencies [self::$code] ['symbol_right'];
			$decimal_place = self::$currencies [self::$code] ['decimal_place'];
			$decimal_point = self::$currencies [self::$code] ['decimal_point'];
			$thousand_point = self::$currencies [self::$code] ['thousand_point'];
			
			$currency = self::$code;
		}
		
		if ($decimal_place_set !== false && is_int ( $decimal_place_set )) {
			$decimal_place = $decimal_place_set;
		}
		
		if (! $value) {
			$value = self::$currencies [$currency] ['value'];
		} 
		
		if ($value) {
			$value = $number * $value;
		} else {
			$value = $number;
		}
		
		$string = '';
		
		if (($symbol_left) && ($format)) {
			$string .= $symbol_left;
		}
		
		if (! $format) {
			$decimal_point = '.';
		}
		
		if (! $format) {
			$thousand_point = '';
		}
		
		$string .= number_format ( round ( $value, ( int ) $decimal_place ), ( int ) $decimal_place, $decimal_point, $thousand_point );
		
		if (($symbol_right) && ($format)) {
			$string .= $symbol_right;
		}
		
		return $string;
	}
	
	public static function formatCode($value, $currency = '') {
		self::construct ();
		if ($currency && self::hasCurrency ( $currency )) {
			$symbol_left = self::$currencies [$currency] ['symbol_left'];
			$symbol_right = self::$currencies [$currency] ['symbol_right'];
			$decimal_place = self::$currencies [$currency] ['decimal_place'];
			$decimal_point = self::$currencies [$currency] ['decimal_point'];
			$thousand_point = self::$currencies [$currency] ['thousand_point'];
		} else {
			$symbol_left = self::$currencies [self::$code] ['symbol_left'];
			$symbol_right = self::$currencies [self::$code] ['symbol_right'];
			$decimal_place = self::$currencies [self::$code] ['decimal_place'];
			$decimal_point = self::$currencies [self::$code] ['decimal_point'];
			$thousand_point = self::$currencies [self::$code] ['thousand_point'];
				
			$currency = self::$code;
		}
	
		$string = '';
	
		if ($symbol_left) {
			$string .= $symbol_left;
		}
	
		$string .= number_format ( round ( $value, ( int ) $decimal_place ), ( int ) $decimal_place, $decimal_point, $thousand_point );
	
		if ($symbol_right) {
			$string .= $symbol_right;
		}
		
		return $string;
	}
	
	public static function normalize($number, $dec = false) {
		self::construct ();
		$value = self::$currencies [self::$code] ['value'];
		if ($value) {
			$value = $number * $value;
		} else {
			$value = $number;
		}
		if (! $dec) {
			$dec = self::$currencies [self::$code] ['decimal_place'];
		}
		return number_format ( round ( $value, ( int ) $dec ), ( int ) $dec, '.', '' );
	}
	
	public static function toDb($value) {
		$value = str_replace ( ',', '.', $value );
		return $value;
	}
	
	// ////////////////////////
	public static function construct() {
		if (! self::$currencies) {
			$request = \Core\Http\Request::getInstance ();
			$currencies = self::getCurrencies ();
			if ($currencies) {
				foreach ( $currencies as $result ) {
					self::$currencies [$result ['code']] = array (
							'id' => $result ['id'],
							'title' => $result ['title'],
							'symbol_left' => $result ['symbol_left'],
							'symbol_right' => $result ['symbol_right'],
							'decimal_place' => $result ['decimal_place'],
							'value' => $result ['value'],
							'decimal_point' => $result ['decimal_point'],
							'thousand_point' => $result ['thousand_point'],
							'code' => $result ['code'] 
					);
				}
			}
			
			/*
			if ($request->getRequest ( 'currency' ) && array_key_exists ( $request->getRequest ( 'currency' ), self::$currencies )) {
				self::setCurrency ( $request->getRequest ( 'currency' ) ); 
			} elseif (\Core\Session\Base::get ( 'currency' ) && array_key_exists ( \Core\Session\Base::get ( 'currency' ), self::$currencies )) { 
				self::setCurrency ( \Core\Session\Base::get ( 'currency' ) ); 
			} elseif ($request->getCookie ( 'currency' ) && array_key_exists ( $request->getCookie ( 'currency' ), self::$currencies )) {
				self::setCurrency ( $request->getCookie ( 'currency' ) ); 
			} else { 
				self::setCurrency ( \Base\Config::get ( 'config_currency' ) );
			} */
			
			self::setCurrency ( \Base\Config::get ( 'config_currency' ) );
			$action = \Core\Base\Action::getInstance();
			if($action->isModuleAccessible('Multicurrency')) {
				$currency = $action->getModule('Multicurrency')->getCurrency();
				if(self::hasCurrency($currency)) {
					self::setCurrency ( $currency );
				}
			}
			
			if (\Core\Http\Request::getInstance ()->getParam('___layout___') == 'admin') { 
				self::setCurrency ( \Base\Config::get ( 'config_currency' ) ); 
			}
			
			//self::setCurrency ( \Base\Config::get ( 'config_currency' ) );
		}
	}
	public static function hasCurrency($currency) {
		self::construct();
		return isset ( self::$currencies [$currency] );
	}
	public static function setCurrency($currency) {
		self::$code = $currency;
// 		if (\Core\Session\Base::get ( 'currency' ) != $currency) {
// 			\Core\Session\Base::set ( 'currency', $currency );
// 		}
// 		if (\Core\Http\Request::getInstance ()->getCookie ( 'currency' ) != $currency) {
// 			setcookie ( 'currency', $currency, time () + 60 * 60 * 24 * 30, '/', \Core\Http\Request::getInstance ()->getServer ( 'HTTP_HOST' ) );
// 		}
	}
	public static function getCurrencyCode($currency = '') {
		self::construct ();
		return self::$code;
	}
	public static function getCurrencyValue($currency = '') {
		self::construct ();
		return self::$currencies [$currency?$currency:self::$code] ['value'];
	}
	public static function getCurrency($currency = '') {
		self::construct ();
		if ($currency && self::hasCurrency ( $currency )) {
			return self::$currencies [$currency];
		} else {
			return self::$currencies [self::$code];
		}
	}
	public static function getCurrencies() {
		$currencyTable = new \Currency\Currency ();
		return $currencyTable->fetchAll ( array (
				'status = 1' 
		) )->toArray ();
	}
	public static function updateCurrencies($code = null, $from_admin = false) {
		if (extension_loaded ( 'curl' )) {
			$currencyTable = new \Currency\Currency ();
			$query = $currencyTable->select ()->where ( 'code != ?', ( string ) ($code ? $code : \Base\Config::get ( 'config_currency' )) );
			
			if (! $from_admin) {
				$query->where ( 'date_modified < ?', \Core\Date::getInstance ( '-1 day', 'yy-mm-dd H:i:s', true )->toString () );
			}
			
			$data = array ();
			$results = $currencyTable->fetchAll ( $query );
			if ($results->count ()) {
				foreach ( $results as $result ) {
					$data [] = \Base\Config::get ( 'config_currency' ) . $result->code . '=X';
				}
				
				$currencyTable->update ( array (
						'value' => '1.00000',
						'date_modified' => new \Core\Db\Expr ( 'NOW()' ) 
				), array (
						'code = ?' => ($code ? $code : \Base\Config::get ( 'config_currency' ))
				) );
			}
			
			if ($data) {
				
				if (ini_get ( 'allow_url_fopen' )) {
					$content = @file_get_contents ( 'http://download.finance.yahoo.com/d/quotes.csv?s=' . implode ( ',', $data ) . '&f=sl1&e=.csv' );
				} else {
					$content = self::file_get_contents_curl ( 'http://download.finance.yahoo.com/d/quotes.csv?s=' . implode ( ',', $data ) . '&f=sl1&e=.csv' );
				}
				
				$lines = explode ( "\n", trim ( $content ) );
				
				foreach ( $lines as $line ) {
					$currency = substr ( $line, 4, 3 );
					$value = substr ( $line, 11, 6 );
					if (( float ) $value) {
						$currencyTable->update ( array (
								'value' => ( float ) $value,
								'date_modified' => new \Core\Db\Expr ( 'NOW()' ) 
						), array (
								'code = ?' => $currency 
						) );
					}
				}
			}
		}
	}
	public static function file_get_contents_curl($url) {
		$ch = curl_init ( $url );
		curl_setopt ( $ch, CURLOPT_HEADER, false );
		if (! ini_get ( 'safe_mode' ) && ! ini_get ( 'open_basedir' )) {
			curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, true );
		}
		curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, 20 );
		curl_setopt ( $ch, CURLOPT_TIMEOUT, 20 );
		curl_setopt ( $ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1" );
		curl_setopt ( $ch, CURLOPT_HTTPGET, true );
		curl_setopt ( $ch, CURLOPT_MAXREDIRS, 5 );
		curl_setopt ( $ch, CURLOPT_MAXCONNECTS, 5 );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		
		$Rec_Data = curl_exec ( $ch );
		curl_close ( $ch );
		return $Rec_Data;
	}
}