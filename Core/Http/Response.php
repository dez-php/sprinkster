<?php

namespace Core\Http;

class Response {
	private static $_instance;
	private $headers = array ();
	private $level = 0;
	private $keepAlive = 0;
	private $request;
	
	/**
	 * Store the next token
	 *
	 * @var string
	 */
	protected $nextToken;
	
	public function getLive() {
		return $this->keepAlive;
	}
	
	public function setLive($keepAlive) {
		$this->keepAlive = $keepAlive;
		return $this;
	}
	
	public function getLevel() {
		return $this->level;
	}
	
	public function setLevel($level) {
		$this->level = $level;
		return $this;
	}
	
	public function setRequest(\Core\Http\Request $request) {
		$this->request = $request;
		return $this;
	}
	
	public function setStatusheader($code = 200, $text = '') {
		$stati = array (
				200 => 'OK',
				201 => 'Created',
				202 => 'Accepted',
				203 => 'Non-Authoritative Information',
				204 => 'No Content',
				205 => 'Reset Content',
				206 => 'Partial Content',
				
				300 => 'Multiple Choices',
				301 => 'Moved Permanently',
				302 => 'Found',
				304 => 'Not Modified',
				305 => 'Use Proxy',
				307 => 'Temporary Redirect',
				
				400 => 'Bad Request',
				401 => 'Unauthorized',
				403 => 'Forbidden',
				404 => 'Not Found',
				405 => 'Method Not Allowed',
				406 => 'Not Acceptable',
				407 => 'Proxy Authentication Required',
				408 => 'Request Timeout',
				409 => 'Conflict',
				410 => 'Gone',
				411 => 'Length Required',
				412 => 'Precondition Failed',
				413 => 'Request Entity Too Large',
				414 => 'Request-URI Too Long',
				415 => 'Unsupported Media Type',
				416 => 'Requested Range Not Satisfiable',
				417 => 'Expectation Failed',
				
				500 => 'Internal Server Error',
				501 => 'Not Implemented',
				502 => 'Bad Gateway',
				503 => 'Service Unavailable',
				504 => 'Gateway Timeout',
				505 => 'HTTP Version Not Supported' 
		);
		
		if ($code == '' or ! is_numeric ( $code )) {
			echo \Core\Base\Action::getInstance ()->showError ( 'Status codes must be numeric', '', 'error_general', 500 );
			exit ();
		}
		
		if (isset ( $stati [$code] ) and $text == '') {
			$text = $stati [$code];
		}
		
		if ($text == '') {
			echo \Core\Base\Action::getInstance ()->showError ( 'No status text available.  Please check your status code number or supply your own message text.', '', 'error_general', 500 );
			exit ();
		}
		
		$server_protocol = (isset ( $_SERVER ['SERVER_PROTOCOL'] )) ? $_SERVER ['SERVER_PROTOCOL'] : FALSE;
		
		if (substr ( php_sapi_name (), 0, 3 ) == 'cgi') {
			$this->addHeader ( "Status: {$code} {$text}", TRUE );
		} elseif ($server_protocol == 'HTTP/1.1' or $server_protocol == 'HTTP/1.0') {
			$this->addHeader ( $server_protocol . " {$code} {$text}", TRUE, $code );
		} else {
			$this->addHeader ( "HTTP/1.1 {$code} {$text}", TRUE, $code );
		}
	}
	
	/**
	 *
	 * @return \Core\Http\Request
	 */
	public function getRequest() {
		if ($this->request == null) {
			$this->setRequest ( \Core\Http\Request::getInstance () );
		}
		return $this->request;
	}
	
	/**
	 *
	 * @param string $header        	
	 * @return \Core\Http\Response
	 */
	public function addHeader($header) {
		$headerName = strtolower ( $this->_tokenize ( $header, ':' ) );
		$headerValue = trim ( chop ( $this->_tokenize ( "\r\n" ) ) );
		$this->headers [$headerName] = $headerValue;
		return $this;
	}
	
	public function redirect($url) {
		header ( 'Location: ' . $url );
		exit ();
	}
	
	/**
	 *
	 * @param array $options        	
	 * @return \Core\Http\Response
	 */
	public static function getInstance($options = array()) {
		if (self::$_instance == null) {
			self::$_instance = new self ( $options );
		}
		return self::$_instance;
	}
	
	public function __construct($options = array()) {
		$this->addHeader('content-type: text/html');
	}
	
	private function compress($data, $level = 0) {
		$request = $this->getRequest ();
		
		if ((strpos ( $request->getServer ( 'HTTP_ACCEPT_ENCODING' ), 'gzip' ) !== FALSE)) {
			$encoding = 'gzip';
		}
		
		if ((strpos ( $request->getServer ( 'HTTP_ACCEPT_ENCODING' ), 'x-gzip' ) !== FALSE)) {
			$encoding = 'x-gzip';
		}
		
		if (! isset ( $encoding )) {
			return $data;
		}
		
		if (! extension_loaded ( 'zlib' ) || ini_get ( 'zlib.output_compression' )) {
			return $data;
		}
		
		if (headers_sent ()) {
			return $data;
		}
		
		if (connection_status ()) {
			return $data;
		}
		
		$this->addHeader ( 'Content-Encoding: ' . $encoding );
		
		return gzencode ( $data, ( int ) $level );
	}

	private function _tokenize($string, $separator = '') {
		if (! strcmp ( $separator, '' )) {
			$separator = $string;
			$string = $this->nextToken;
		}
	
		for($character = 0; $character < strlen ( $separator ); $character ++) {
			if (gettype ( $position = strpos ( $string, $separator [$character] ) ) == "integer") {
				$found = (isset ( $found ) ? min ( $found, $position ) : $position);
			}
		}
	
		if (isset ( $found )) {
			$this->nextToken = substr ( $string, $found + 1 );
			return (substr ( $string, 0, $found ));
		} else {
			$this->nextToken = '';
			return ($string);
		}
	}
	
	public function appendBody($body)
	{
		if ($this->level)
		{
			ini_set ( "zlib.output_compression", 4096 );
			$body = $this->compress ( $body, $this->level );
		}
		
		if ($this->keepAlive)
			$this->addHeader("Keep-Alive: timeout=" . (int) $this->keepAlive);
		
		if (!headers_sent())
			foreach($this->headers as $key => $value)
				header($key . ':' . $value, TRUE);
		
		if(isset($this->headers['content-type']) && $this->headers['content-type'] == 'text/html')
			\Core\Base\Action::getComponent('document')->render($body);
		
		exit( $body );
	}
}
 
