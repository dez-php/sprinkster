<?php

namespace Core\Color;

class Colormap {

	const PERCENTAGE = 2;
	const PRECISION = 5;
	const RESULTS = 50;
	const TRUEPER = true;

	public $image;
	public $height;
	public $width;
	public $type_int;
	public $precision;
	public $workingImage;
	public $coinciditions;
	public $maxnumcolors;
	public $trueper;
	public $color_map = array();
	public $_palette = array();
	public $_min_percentage = 5;
	public $_excluded_colors = array( '#FFFFFF', '#000000' );
	public $palete = '["#660000", "#990000", "#cc0000", "#cc3333", "#ea4c88", "#993399", "#663399", "#333399", "#0066cc", "#0099cc", "#66cccc", "#77cc33", "#669900", "#336600", "#666600", "#999900", "#cccc33", "#ffff00", "#ffcc33", "#ff9900", "#ff6600", "#cc6633", "#996633", "#663300", "#000000", "#999999", "#cccccc", "#ffffff", "#E7D8B1", "#FDADC7", "#424153", "#ABBCDA", "#F5DD01"]';

	static $hit_pixels = 0;
	static $missed_pixels = 0;
	
	public $PREVIEW_WIDTH    = 150;
	public $PREVIEW_HEIGHT   = 150;

	public function __construct( $image, $precision = 10, $maxnumcolors = 5, $trueper = true ) {

		$this->image = $image;
		$this->maxnumcolors = $maxnumcolors;
		$this->trueper = $trueper;
		$this->getImageSize();
		$this->precision = $precision;

		$this->readPixels();

		//$this->_excluded_colors[] = $this->getBackgroundColor();
	}

	public function setMinPercentage( $num ) {
		$this->_min_percentage = $num;
	}

	public function readPixels() {

		$image 		= $this->image;
		$width 		= $this->width;
		$height 	= $this->height;
	
		try {
			$size = getimagesize($image);
			switch ( $this->type_int ) {
				case 3:
					$outputimg = "imagecreatefrompng";
					break;
				case 2:
					$outputimg = "imagecreatefromjpeg";
				break;
				case 1:
					$outputimg = "imagecreatefromgif";
					break;
				case 15:
					$outputimg = "imagecreatefromwbmp";
					break;
				default: return;
			}
			
			$this->workingImage = $outputimg($image);
			
			$scale = 1;
			if ($this->width>0)
				$scale = min($this->PREVIEW_WIDTH/$this->width, $this->PREVIEW_HEIGHT/$this->height);
			if ($scale < 1)
			{
				$width = floor($scale*$this->width);
				$height = floor($scale*$this->height);
			}
			else
			{
				$width = $this->width;
				$height = $this->height;
			}
			$image_resized = imagecreatetruecolor($width, $height);
			// WE NEED NEAREST NEIGHBOR RESIZING, BECAUSE IT DOESN'T ALTER THE COLORS
			imagecopyresampled($image_resized, $this->workingImage, 0, 0, 0, 0, $width, $height, $size[0], $size[1]);
			$this->workingImage = $image_resized;
			$this->width = imagesx($this->workingImage);
			$this->height = imagesy($this->workingImage);

		} catch (\Exception $e) {
			echo $e->getMessage()."\n";
			exit();
		}

		for( $x = 0; $x < $width; $x += $this->precision ) {
			for ( $y = 0; $y < $height; $y += $this->precision ) {
				
				$index = imagecolorat($this->workingImage, $x, $y);
				$rgb = imagecolorsforindex($this->workingImage, $index);

				$color = $this->getClosestColor( $rgb["red"], $rgb["green"], $rgb["blue"] );

				$hexarray[] = $this->RGBToHex( $color[0], $color[1], $color[2] );
			}
		}
		
		$coinciditions = array_count_values($hexarray);
		$this->coinciditions = $coinciditions;

		return true;
	}

	public function getBackgroundColor( $use_palette = true, $_percents = 60 ) {
		/* mod by joro */
		
		if(!$this->workingImage) {
			return null;
		}
		
		$width 		= $this->width;
		$height 	= $this->height;
		
		$colors = array();
		for( $x = 0; $x < $width; $x ++ ) {
			$index = imagecolorat($this->workingImage, $x, 0);
			$rgb = imagecolorsforindex($this->workingImage, $index);
			if ( $use_palette ) {
				$color = $this->getClosestColor( $rgb["red"], $rgb["green"], $rgb["blue"] );
			} else {
				$color = array($rgb["red"], $rgb["green"], $rgb["blue"]);
			}
			$hexarray = $this->RGBToHex( $color[0], $color[1], $color[2] );
			if(in_array(strtoupper($hexarray), $this->_excluded_colors)) continue;
			if(isset($colors[$hexarray])) {
				$colors[$hexarray]++;
			} else {
				$colors[$hexarray]=1;
			}
			$index = imagecolorat($this->workingImage, $x, $height-1);
			$rgb = imagecolorsforindex($this->workingImage, $index);
			if ( $use_palette ) {
				$color = $this->getClosestColor( $rgb["red"], $rgb["green"], $rgb["blue"] );
			} else {
				$color = array($rgb["red"], $rgb["green"], $rgb["blue"]);
			}
			$hexarray = $this->RGBToHex( $color[0], $color[1], $color[2] );
			if(isset($colors[$hexarray])) {
				$colors[$hexarray]++;
			} else {
				$colors[$hexarray]=1;
			}
		}
		
		for ( $y = 0; $y < $height; $y ++ ) {
			$index = imagecolorat($this->workingImage, 0, $y);
			$rgb = imagecolorsforindex($this->workingImage, $index);
			if ( $use_palette ) {
				$color = $this->getClosestColor( $rgb["red"], $rgb["green"], $rgb["blue"] );
			} else {
				$color = array($rgb["red"], $rgb["green"], $rgb["blue"]);
			}
			$hexarray = $this->RGBToHex( $color[0], $color[1], $color[2] );
			if(in_array(strtoupper($hexarray), $this->_excluded_colors)) continue;
			if(isset($colors[$hexarray])) {
				$colors[$hexarray]++;
			} else {
				$colors[$hexarray]=1;
			}
			$index = imagecolorat($this->workingImage, $width-1, $y);
			$rgb = imagecolorsforindex($this->workingImage, $index);
			if ( $use_palette ) {
				$color = $this->getClosestColor( $rgb["red"], $rgb["green"], $rgb["blue"] );
			} else {
				$color = array($rgb["red"], $rgb["green"], $rgb["blue"]);
			}
			$hexarray = $this->RGBToHex( $color[0], $color[1], $color[2] );
			if(isset($colors[$hexarray])) {
				$colors[$hexarray]++;
			} else {
				$colors[$hexarray]=1;
			}
		}
		
		if(!$colors) 
			return null;
		
		arsort($colors);
		
		list($hexcolor, $sum) = each($colors);
		if( ( $sum / (($width*2)+($height*2)) ) * 100 > $_percents ) {
			return $hexcolor;
		}

		return null;
	}
	
	static public function RGBToHex($r, $g, $b){
	
		$hex = "#";
		$hex.= str_pad( dechex($r), 2, "0", STR_PAD_LEFT );
		$hex.= str_pad( dechex($g), 2, "0", STR_PAD_LEFT );
		$hex.= str_pad( dechex($b), 2, "0", STR_PAD_LEFT );

		return strtoupper($hex);
	}

	static public function HexToRGB($hex) {
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
	
	private function getPercentageOfColors(){
	
		$coinciditions = $this->coinciditions;

		$total = 0;

		foreach ($coinciditions as $color => $cuantity) {

			if ( in_array( $color, $this->_excluded_colors ) )
				unset( $coinciditions[$color] );

			else
				$total += $cuantity;
		}

		foreach ($coinciditions as $color => $cuantity) {
			$percentage = (($cuantity/$total)*100);
			$finallyarray["$color"] = $percentage;
		}

		if ( ! $coinciditions )
			return array();

		asort($finallyarray);
		array_keys($finallyarray);
		$outputarray = array_slice(array_reverse($finallyarray), 0, $this->maxnumcolors);
	
		$trueper = $this->trueper;
	
		if( $trueper && $outputarray ) {
		
			   $total = 0;
			   foreach ($outputarray as $color => $cuantity) {
				   $total += $cuantity;
			   }
			   foreach ($outputarray as $color => $cuantity) {
				   $percentage = (($cuantity/$total)*100);
				   $finallyarrayp["$color"] = $percentage;
			   }
			   return $finallyarrayp;

		} else {
		
		   return $outputarray;
		}
	}

	public function getImageSize() {
	
		$imgsize 	= @getimagesize($this->image);
		$height 	= $imgsize[1];
		$width 		= $imgsize[0];
		$this->height = $height;
		$this->width = $width;
		$this->type_int = $imgsize[2];

		return "x= ".$width."y= ".$height;
	}

	public function getProminentColors() {

		$pixels 		= $this->getPercentageOfColors();
		//$bg_color_hex 	= $this->getBackgroundColor($_percents);


		foreach ($pixels as $color => $value) {
			if ( $value < $this->_min_percentage )
				unset( $pixels[$color] );
		}

		$_c = array();

		foreach ($pixels as $key => $value) {
			$_c[$key] = $value;
		}

		return $_c;
	}

	/**
	 * Function used for testing, will return a map pof all colors to their matching color counter part
	 */
	public function getColorMap() {

		$image 		= $this->image;
		$width 		= $this->width;
		$height 	= $this->height;

		for( $x = 0; $x < $width; $x += $this->precision ) {
			for ( $y = 0; $y < $height; $y += $this->precision ) {
				
				$index = imagecolorat($this->workingImage, $x, $y);
				$rgb = imagecolorsforindex($this->workingImage, $index);

				$color = $this->getClosestColor( $rgb["red"], $rgb["green"], $rgb["blue"] );

				$hexarray[ $this->RGBToHex( $rgb["red"], $rgb["green"], $rgb["blue"] ) ] = $this->RGBToHex( $color[0], $color[1], $color[2] );
			}
		}

		return $hexarray;
	}

	private function getClosestColor($r, $g, $b){

		if ( isset( $this->color_map[$this->RGBToHex( $r, $g, $b ) ] ) ) {
			return $this->color_map[$this->RGBToHex( $r, $g, $b )];
		}

		$differencearray = array();
		$colors = $this->getPalette();

		foreach ($colors as $key => $value) {
			$value = $value['rgb'];
			$differencearray[$key] = self::getDistanceBetweenColors( $value, array( $r, $g, $b ) );
		}

		$smallest = min( $differencearray );

		$key = array_search($smallest, $differencearray);

		$color = $this->color_map[$this->RGBToHex( $r, $g, $b )] = $colors[$key]['rgb'];

		return $color;
	}

	private static function getDistanceBetweenColors( $col1, $col2 ) {

		$xyz1 = self::rgb_to_xyz( $col1 );
		$xyz2 = self::rgb_to_xyz( $col2 );

		$lab1 = self::xyz_to_lab( $xyz1 );
		$lab2 = self::xyz_to_lab( $xyz2 );

		return \Core\Color\ColormapFnc::ciede2000( $lab1, $lab2 );
	}

	private function getPalette() {

		if ( ! empty( $this->_palette ) )
			return $this->_palette;

		$str = $this->palete;

		$hexs = json_decode( $str );

		foreach ( $hexs as $hex )
			$this->_palette[] = array( 'rgb' => self::HexToRGB( $hex ), 'hex' => $hex );

		return $this->_palette;
	}
	
	private static function xyz_to_lab( $xyz ){
		 $x = $xyz[0];
		 $y = $xyz[1];
		 $z = $xyz[2];
		 $_x = $x/95.047;
		 $_y = $y/100;
		 $_z = $z/108.883;
		 if($_x>0.008856){
			  $_x = pow($_x,1/3);
		 }
		 else{
			  $_x = 7.787*$_x + 16/116;
		 }
		 if($_y>0.008856){
			  $_y = pow($_y,1/3);
		 }
		 else{
			  $_y = (7.787*$_y) + (16/116);
		 }
		 if($_z>0.008856){
			  $_z = pow($_z,1/3);
		 }
		 else{
			  $_z = 7.787*$_z + 16/116;
		 }
		 $l= 116*$_y -16;
		 $a= 500*($_x-$_y);
		 $b= 200*($_y-$_z);
		 
		 return(array( 'l' => $l, 'a' => $a, 'b' => $b));
	}


	private static function rgb_to_xyz( $rgb ) {
		$red = $rgb[0];
		$green = $rgb[1];
		$blue = $rgb[2]; 
		$_red = $red/255;
		$_green = $green/255;
		$_blue = $blue/255;

		if ( $_red > 0.04045 ) {
			$_red = ($_red+0.055)/1.055;
			$_red = pow($_red,2.4);
		} else{
			$_red = $_red/12.92;
		}

		if ( $_green > 0.04045 ) {
		  $_green = ($_green+0.055)/1.055;
		  $_green = pow($_green,2.4);     
		} else{
		  $_green = $_green/12.92;
		}

		if ( $_blue>0.04045 ) {
		  $_blue = ($_blue+0.055)/1.055;
		  $_blue = pow($_blue,2.4);     
		} else {
		  $_blue = $_blue/12.92;
		}

		$_red *= 100;
		$_green *= 100;
		$_blue *= 100;
		$x = $_red * 0.4124 + $_green * 0.3576 + $_blue * 0.1805;
		$y = $_red * 0.2126 + $_green * 0.7152 + $_blue * 0.0722;
		$z = $_red * 0.0193 + $_green * 0.1192 + $_blue * 0.9505;
		return(array( $x, $y, $z ));
	}


	private static function de_1994( $lab1,$lab2 ) {
		$c1 = sqrt($lab1[1]*$lab1[1]+$lab1[2]*$lab1[2]);
		$c2 = sqrt($lab2[1]*$lab2[1]+$lab2[2]*$lab2[2]);
		$dc = $c1-$c2;
		$dl = $lab1[0]-$lab2[0];
		$da = $lab1[1]-$lab2[1];
		$db = $lab1[2]-$lab2[2];

		$dh = ( ( $dh_sq = ( ($da*$da)+($db*$db)-($dc*$dc) ) ) < 0 ) ? sqrt( $dh_sq * -1  ) : sqrt( $dh_sq );

		$first = $dl;
		$second = $dc/(1+0.045*$c1);
		$third = $dh/(1+0.015*$c1);
		return(sqrt($first*$first+$second*$second+$third*$third));
	}
}