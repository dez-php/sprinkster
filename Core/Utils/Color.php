<?php

namespace Core\Utils;

class Color {


	/**
	 * Convert hex colors to RGB
	 *
	 * @param   string    $hex       Single color in hexadecimal.
	 * @return  array     $rgb       RGB representing input color.
	 */
	public static function HexToRGB($hex) {
	   $hex = str_replace("#", "", $hex);
	 
	   if(strlen($hex) == 3) {
		  $r = hexdec(substr($hex,0,1).substr($hex,0,1));
		  $g = hexdec(substr($hex,1,1).substr($hex,1,1));
		  $b = hexdec(substr($hex,2,1).substr($hex,2,1));
	   } else {
		  $r = hexdec(substr($hex,0,2));
		  $g = hexdec(substr($hex,2,2));
		  $b = hexdec(substr($hex,4,2));
	   }
	   $rgb = array($r, $g, $b);

	   return $rgb; // returns an array with the rgb values
	}
	
	public static function RGBToHex($r, $g, $b){
	
		$hex = "#";
		$hex.= str_pad( dechex($r), 2, "0", STR_PAD_LEFT );
		$hex.= str_pad( dechex($g), 2, "0", STR_PAD_LEFT );
		$hex.= str_pad( dechex($b), 2, "0", STR_PAD_LEFT );

		return strtoupper($hex);
	}
		
	/**
	 * Convert three-digit hex codes to six digits.
	 * Preserves hash character if input contains one.
	 * 
	 * @param   string    Three character hex code.
	 * @return  string    Same hex codes, in its six character format.
	 */
	public function HexToSix($color)
	{
		$prefix = "";
		if ($color[0] == "#") {
			$prefix = "#";
			$color = str_replace('#', '', $color);
		}
		if (strlen($color) == 6) {
			return $prefix . $color;
		}
		$color6char  = $color[0] . $color[0];
		$color6char .= $color[1] . $color[1];
		$color6char .= $color[2] . $color[2];
		return $prefix . $color6char;
	}

	
	
}