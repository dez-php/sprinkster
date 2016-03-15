<?php

namespace Core\Http;

/*
 * use class test { public static function aaa($a) { echo nl2br($a); } }
 * \Core\Http\Thread::listen(); $a = "adsadas\nfdsfds fds fds fds";
 * \Core\Http\Thread::run('test->aaa',$a); for($i=0; $i<10; $i++) { $a .= ' ' .
 * $i; Core_Thread::run(array('test','aaa'),$a); #OR $a .= ' ' . $i;
 * Core_Thread::run('test->aaa',$a); }
 */
class Thread {
	const THREAD_CALL_SIGNATURE_ID = 'THREAD_CALL_CORE';
	public static $log = true;
	public static function listen() {
		global $HTTP_RAW_POST_DATA;
		$content_type = isset ( $_SERVER ['CONTENT_TYPE'] ) ? $_SERVER ['CONTENT_TYPE'] : '';
		if ($content_type === 'application/xml' && $_SERVER ['REQUEST_METHOD'] == 'POST' && ($postText = isset ( $HTTP_RAW_POST_DATA ) ? $HTTP_RAW_POST_DATA : file_get_contents ( 'php://input' )) != '') {

			$gXml = new \DomDocument ();
			$gXml->strictErrorChecking = false;
			$gXml->preserveWhiteSpace = true;
			$gXml->loadXML ( $postText );
			$gXpath = new \DOMXPath ( $gXml );
			
			$query = "//THREAD_CALL/THREAD_CALL_SIGNATURE_ID";

			$THREAD_CALL_SIGNATURE_ID = $gXpath->query ( $query );
			
			$query = "//THREAD_CALL/CLASS";
			$CLASS = $gXpath->query ( $query );
			
			$query = "//THREAD_CALL/CLASS_CONSTRUCT";
			$CLASS_CONSTRUCT = $gXpath->query ( $query );
			
			$query = "//THREAD_CALL/FUNCTION";
			$FUNCTION = $gXpath->query ( $query );
			
			$query = "//THREAD_CALL/ARGS";
			$ARGS = $gXpath->query ( $query );
			
			$query = "//THREAD_CALL/LOG";
			$LOG = $gXpath->query ( $query );
			
			// clean of ob_start
			while ( ob_get_level () > 0 ) {
				ob_end_clean ();
			}
			//
			ob_implicit_flush ();
			
			if (! $THREAD_CALL_SIGNATURE_ID->length or ! $FUNCTION->length) {
				if ($LOG) {
					\Core\Log::write ( 'NOT_FOUND_THREAD_CALL_SIGNATURE_ID' );
				}
				exit ();
			}
			
			$THREAD_CALL_SIGNATURE_ID = trim ( $THREAD_CALL_SIGNATURE_ID->item ( 0 )->nodeValue );
			$CLASS = unserialize ( base64_decode ( trim ( $CLASS->item ( 0 )->nodeValue ) ) );
			$CLASS_CONSTRUCT = unserialize ( base64_decode ( trim ( $CLASS_CONSTRUCT->item ( 0 )->nodeValue ) ) );
			$FUNCTION = trim ( $FUNCTION->item ( 0 )->nodeValue );
			
			if ($ARGS->length) {
				$ARGS = unserialize ( trim ( $ARGS->item ( 0 )->nodeValue ) );
			} else {
				$ARGS = array ();
			}
			
			//
			if ($THREAD_CALL_SIGNATURE_ID != self::THREAD_CALL_SIGNATURE_ID) {
				if ($LOG) {
					\Core\Log::write ( 'THREAD_CALL_SIGNATURE_ID' );
				}
				exit ();
			}
			
			try {
				if ($CLASS) {
					if (is_object ( $CLASS )) {
						$object = $CLASS;
					} else if ($CLASS_CONSTRUCT) {
						if (is_array ( $CLASS_CONSTRUCT )) {
							$object = call_user_func_array ( create_function ( '', 'return new ' . $CLASS . '(' . implode ( ',', $CLASS_CONSTRUCT ) . ');' ), array () );
						} else {
							$object = call_user_func_array ( create_function ( '$a', 'return new ' . $CLASS . '($a);' ), array (
									$CLASS_CONSTRUCT 
							) );
						}
					} else {
						$object = new $CLASS ();
					}
					if (! is_callable ( array (
							$object,
							$FUNCTION 
					) )) {
						if ($LOG) {
							\Core\Log::write ( 'NOT_CALLABLE' );
						}
						exit ();
					}
					call_user_func_array ( array (
							$object,
							$FUNCTION 
					), $ARGS );
				} else {
					if (! is_callable ( $FUNCTION )) {
						if ($LOG) {
							\Core\Log::write ( 'NOT_CALLABLE' );
						}
						exit ();
					}
					call_user_func_array ( $FUNCTION, $ARGS );
				}
			} catch ( \Core\Exception $e ) {
				if ($LOG) {
					\Core\Log::write ( $e->getMessage () );
				}
			}
			exit ();
		}
	}
	private static function post($xml_data, $skey) {
		$parts = parse_url ( /*self::fullUrl ()*/ \Core\Http\Request::getInstance ()->getBaseUrl () );
		
		// Start out with a blocking socket.
		$errno = 0;
		$errstr = '';
		$fp = fsockopen ( $parts ['host'], isset ( $parts ['port'] ) ? $parts ['port'] : 80, $errno, $errstr, 5 );
		if (! $fp) {
			// unset ( self::$gThread_Responses [$skey] );
			return false;
		}
		
		$header = "POST " . $parts ['path'] . " HTTP/1.1\r\n";
		$header .= "Host: " . $parts ['host'] . "\r\n";
		$header .= "Content-Type: application/xml\r\n";
		$header .= "Content-Length: " . strlen ( $xml_data ) . "\r\n";
		$header .= "Connection: Close\r\n\r\n";
		$header .= $xml_data;
		
		fputs ( $fp, $header );
		fclose ( $fp );
	}
	private static function fullUrl() {
		$s = &$_SERVER;
		$ssl = (! empty ( $s ['HTTPS'] ) && $s ['HTTPS'] == 'on') ? true : false;
		$sp = strtolower ( $s ['SERVER_PROTOCOL'] );
		$protocol = substr ( $sp, 0, strpos ( $sp, '/' ) ) . (($ssl) ? 's' : '');
		$port = $s ['SERVER_PORT'];
		$port = ((! $ssl && $port == '80') || ($ssl && $port == '443')) ? '' : ':' . $port;
		$host = isset ( $s ['HTTP_X_FORWARDED_HOST'] ) ? $s ['HTTP_X_FORWARDED_HOST'] : isset ( $s ['HTTP_HOST'] ) ? $s ['HTTP_HOST'] : $s ['SERVER_NAME'];
		return $protocol . '://' . $host . $port . $s ['REQUEST_URI'];
	}
	public static function run() {
		$args = func_get_args ();
		if (empty ( $args )) {
			return false;
		}
		
		$callto = array_shift ( $args );
		
		$class = null;
		$method = null;
		$args_construct = null;
		$skey = 'n/a';
		if (is_array ( $callto )) {
			if (count ( $callto ) > 1) {
				$class = $callto [0];
				$method = $callto [1];
				if (count ( $callto ) == 3) {
					$args_construct = $callto [2];
				} elseif (count ( $callto ) > 3) {
					for($i = 2; $i < count ( $callto ); $i ++) {
						$args_construct [] = $callto [$i];
					}
				}
			}
		} elseif (is_string ( $callto ) && preg_match ( '/^([a-z0-9_]{1,})(->|::)([a-z0-9_]{1,})$/i', $callto, $match )) {
			$class = $match [1];
			$method = $match [3];
		} else {
			$class = '';
			$method = $callto;
		}
		
		// no need
		unset ( $callto );
		
		if (! $method) {
			return false;
		}
		
		//
		$params = serialize ( $args );
		
		$xml_data = '<?xml version="1.0" encoding="UTF-8"?>' . "\r\n";
		$xml_data .= "<THREAD_CALL>\r\n";
		
		$xml_data .= "\t<THREAD_CALL_SIGNATURE_ID>\r\n";
		$xml_data .= "<![CDATA[\r\n";
		$xml_data .= self::THREAD_CALL_SIGNATURE_ID;
		$xml_data .= "\r\n]]>\r\n";
		$xml_data .= "\t</THREAD_CALL_SIGNATURE_ID>\r\n";
		
		$xml_data .= "\t<CLASS>\r\n";
		$xml_data .= "<![CDATA[\r\n";
		$xml_data .= base64_encode ( serialize ( $class ) );
		$xml_data .= "\r\n]]>\r\n";
		$xml_data .= "\t</CLASS>\r\n";
		
		$xml_data .= "\t<CLASS_CONSTRUCT>\r\n";
		$xml_data .= "<![CDATA[\r\n";
		$xml_data .= base64_encode ( serialize ( $args_construct ) );
		$xml_data .= "\r\n]]>\r\n";
		$xml_data .= "\t</CLASS_CONSTRUCT>\r\n";
		
		$xml_data .= "\t<FUNCTION>\r\n";
		$xml_data .= "<![CDATA[\r\n";
		$xml_data .= $method;
		$xml_data .= "\r\n]]>\r\n";
		$xml_data .= "\t</FUNCTION>\r\n";
		
		$xml_data .= "\t<ARGS>\r\n";
		$xml_data .= "<![CDATA[\r\n";
		$xml_data .= $params;
		$xml_data .= "\r\n]]>\r\n";
		$xml_data .= "\t</ARGS>\r\n";
		
		$xml_data .= "\t<LOG>\r\n";
		$xml_data .= "<![CDATA[\r\n";
		$xml_data .= self::$log;
		$xml_data .= "\r\n]]>\r\n";
		$xml_data .= "\t</LOG>\r\n";
		
		$xml_data .= "</THREAD_CALL>";

		self::post ( $xml_data, $skey );
	}
}