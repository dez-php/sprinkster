<?php

namespace Local\Library;

class Thumb {
	
    /**
     * @var \Local\Library\Thumb\Gd
     */
    private $image = null;
    
    private $img_info;
		
	public function __construct($file) {
// 		$file = preg_replace('#([/]{2,})#','/',$file);

		$imageSize = new \Core\Image\Getimagesize($file);
		if ( is_array($info = $imageSize->getSize()) ) {

			$info['extension'] = \Core\File\Ext::getExtFromMime($info['mime']);
			$info['isAnimated'] = $this->isAnimated($file);
			$this->img_info = $info;
			if ($info['mime'] == 'image/gif' && $this->isAnimated($file)) {
				require_once __DIR__ . '/Thumb/GdAnimated.php';
				$this->image = new \Local\Library\Thumb\GdAnimated($file, $info);
			} else {
				if(class_exists('\Imagick', false)) {
					require_once __DIR__ . '/Thumb/Imagick.php';
					$this->image = new \Local\Library\Thumb\Imagick($file, $info);
				} else {
					require_once __DIR__ . '/Thumb/Gd.php';
					$this->image = new \Local\Library\Thumb\Gd($file, $info);
				} 
			}

    	} else {
			$this->image = null;
			return $this;
      		throw new \Core\Exception('Error: Could not load image ' . $file . '!');
    	}
	}
	
	public function gray() {
		if(!$this->image) { return; }
		$this->image->gray();
	}
	
    public function save($file, $gray = false, $quality = 75) {
		if(!$this->image) { return false; }
    	return $this->image->save($file, $gray, $quality);
    }	    
	
    public function resize($width = 0, $height = 0, $scaled = true) {
		if(!$this->image) { return $this; }
    	$this->image->resize($width, $height, $scaled);
    } 
	
    public function thumb($width = 0, $height = 0) {
		if(!$this->image) { return $this; }
    	$this->image->thumb($width, $height);
    }
	
	public function resizeWidth($width) {
		if(!$this->image) { return $this; }
		$height = ceil($this->img_info[1] / ($this->img_info[0]/$width));
		$this->image->thumb($width, $height);
	}
	
	public function resizeHeight($height) {
		if(!$this->image) { return $this; }
		$width = ceil($this->img_info[0] / ($this->img_info[1]/$height));
		$this->image->thumb($width, $height);
	}
    
    public function watermark($file, $position = 'bottomright') {
		if(!$this->image) { return $this; }
    	$this->image->watermark($file, $position);
    }
    
    public function crop($top_x, $top_y, $bottom_x, $bottom_y) {
		if(!$this->image) { return $this; }
    	$this->image->crop($top_x, $top_y, $bottom_x, $bottom_y);
    }
    
    public function resize_crop($width, $height) {
		if(!$this->image) { return $this; }
    	$this->image->resize_crop($width, $height);
    }
    
    public function rotate($degree, $color = 'FFFFFF') {
		if(!$this->image) { return $this; }
    	$this->image->rotate($degree, $color);
    }
	    
    public function filter($filter) {
		if(!$this->image) { return $this; }
    	$this->image->filter($filter);
    }
            
    public function text($text, $x = 0, $y = 0, $size = 5, $color = '000000') {
		if(!$this->image) { return $this; }
    	$this->image->text($text, $x, $y, $size, $color);
    }
    
    public function merge($file, $x = 0, $y = 0, $opacity = 100) {
		if(!$this->image) { return $this; }
    	$this->image->merge($file, $x, $y, $opacity);
    }
	
	public function getInfo() {
		if(!$this->image) { return false; }
		return $this->image->getInfo();
	}
	
	public static function isAnimated($filename) {
		if(!($fh = @fopen($filename, 'rb'))) {
			return false;
		}
		$count = 0;
		//an animated gif contains multiple "frames", with each frame having a
		//header made up of:
		// * a static 4-byte sequence (\x00\x21\xF9\x04)
		// * 4 variable bytes
		// * a static 2-byte sequence (\x00\x2C) (some variants may use \x00\x21 ?)
	
		// We read through the file til we reach the end of the file, or we've found
		// at least 2 frame headers
		while(!feof($fh) && $count < 2) {
			$chunk = fread($fh, 1024 * 100); //read 100kb at a time
			$count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00(\x2C|\x21)#s', $chunk, $matches);
		}
	
		fclose($fh);
		return $count > 1;
	}
	

}

?>