<?php

namespace Facebook\Helper;

class Photo {
	
	private static $allow = [
		'image/jpeg',
		'image/jpg',
		'image/png',
		'image/gif'
	];

    /**
     * Get the real url for picture to use after
     */
    public static function getRealUrl($photoLink, $size = 'large') {

    	$size = in_array(strtolower($size), ['square','small','normal','large']) ? strtolower($size) : 'large';
    	
    	$photoLink .= strpos($photoLink, '?') !== false ? '&' : '?';
    	$photoLink .= 'type=' . $size;
    	
    	$curl = new \Core\Http\Curl();
    	$curl->setTarget($photoLink);
    	$curl->execute();
    	
    	$headers = $curl->getHeaders();
    	if(!is_array($headers)) { $headers = []; }
    	if( !array_key_exists('content-type', $headers) || !array_key_exists('location', $headers) || !self::in_array($headers['content-type'], self::$allow) )
    		return false;
    	return $headers['location'];
    }
    
    private function in_array($array1, $array2) {
    	if(!is_array($array1)) { $array1 = [$array1]; }
    	if(!is_array($array2)) { $array2 = [$array2]; }
    	foreach($array1 AS $a) {
    		if(in_array($a, $array2))
    			return true;
    	}
    	return false;
    }
	
}

?>