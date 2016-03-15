<?php

namespace Local\Library\Thumb;

class Gd {
	
	private $file;
    private $image;
    private $info;
		
	public function __construct($file, $info) {
		$this->file = $file;

		$this->info = array(
			'width'  => $info[0],
			'height' => $info[1],
			'bits'   => $info['bits'],
			'mime'   => $info['mime'],
			'extension' => $info['extension'],
			'isAnimated' => $info['isAnimated']
		);
        	
		$this->image = $this->create($file);
			
		if(in_array($info['mime'], ['image/jpeg','image/jpg']) && function_exists('exif_read_data')) {
			$exif = @exif_read_data($file);
			if(isset($exif['Orientation'])) {
				switch($exif['Orientation']) {
					case 8:
						$this->rotate(90);
					break;
					case 3:
						$this->rotate(180);
					break;
					case 6:
						$this->rotate(-90);
					break;
				}
			}
		}
	}
	
	public function gray() {
		imagefilter($this->image, IMG_FILTER_GRAYSCALE);
	}
		
	private function create($image) {
		$mime = $this->info['mime'];

		if ($mime == 'image/gif') {
			return imagecreatefromgif($image);
		} elseif ($mime == 'image/png') {
			return imagecreatefrompng($image);
		} elseif ($mime == 'image/jpeg') {
			return imagecreatefromjpeg($image);
		}
    }	
	
    public function save($file, $gray = false, $quality = 75, $destroy = true) {
        $info = pathinfo($file);
        $extension = strtolower($info['extension']);
   
        if($gray) {
        	$this->gray();
        }
        
        $res = null;
        if ($extension == 'jpeg' || $extension == 'jpg') {
            $res = imagejpeg($this->image, $file, $quality);
        } elseif($extension == 'png') {
            $res = imagepng($this->image, $file);
        } elseif($extension == 'gif') {
            $res = imagegif($this->image, $file);
        }
		if($destroy) {
	    	imagedestroy($this->image);
		}
	    return $res;
    }	
	
    public function show($file, $gray = false, $quality = 75, $destroy = true) {
        $mime = $this->info['mime'];
   
        if($gray) {
        	$this->gray();
        }
        
        $res = null;
        if ($mime == 'image/jpeg') {
        	header("Content-Type: image/jpeg");
            $res = imagejpeg($this->image, false, $quality);
        } elseif($mime == 'image/png') {
        	header("Content-Type: image/png");
            $res = imagepng($this->image);
        } elseif($mime == 'image/gif') {
        	header("Content-Type: image/gif");
            $res = imagegif($this->image);
        }
		if($destroy) {
	    	imagedestroy($this->image);
		}
	    return $res;
    }	       
	
    public function thumb($width = 0, $height = 0) {
    	if (!$this->info['width'] || !$this->info['height']) {
			return;
		}

		$xpos = 0;
		$ypos = 0;

		$scale = min($width / $this->info['width'], $height / $this->info['height']);
		
		$width = ceil($this->info['width'] * $scale);
		$height = ceil($this->info['height'] * $scale);
		
		if($width > $this->info['width'] || $height > $this->info['height']) {
			$width = $this->info['width'];
			$height = $this->info['height'];
		}
        		        
       	$image_old = $this->image;
        $this->image = imagecreatetruecolor($width, $height);
	
        $this->image = $this->transparency($this, imagecreatetruecolor($width, $height));
        
        imagecopyresampled($this->image, $image_old, $xpos, $ypos, 0, 0, $width, $height, $this->info['width'], $this->info['height']);
        imagedestroy($image_old);
        
        $this->info['width']  = $width;
        $this->info['height'] = $height;
    }
	
    public function resize($width = 0, $height = 0, $scaled = true) {
    	if (!$this->info['width'] || !$this->info['height']) {
			return;
		}

		$xpos = 0;
		$ypos = 0;

		$scale = min($width / $this->info['width'], $height / $this->info['height']);
		
		if($scaled) {
			$new_width = ceil($this->info['width'] * $scale);
			$new_height = ceil($this->info['height'] * $scale);			
	    	$xpos = ceil(($width - $new_width) / 2);
	   		$ypos = ceil(($height - $new_height) / 2);
		} else {
			$new_width = $width;
			$new_height = $height;
		}
        		        
       	$image_old = $this->image;
        $this->image = imagecreatetruecolor($width, $height);
	
        $this->image = $this->transparency($this, imagecreatetruecolor($width, $height));
        
        imagecopyresampled($this->image, $image_old, $xpos, $ypos, 0, 0, $new_width, $new_height, $this->info['width'], $this->info['height']);
        imagedestroy($image_old);
        
        $this->info['width']  = $width;
        $this->info['height'] = $height;
    }
    
    public function watermark($file, $position = 'bottomright') {
    	
    	$imageSize = new \Core\Image\Getimagesize($file);
    	if ( is_array($info = $imageSize->getSize()) ) {
    		$info['extension'] = \Core\File\Ext::getExtFromMime($info['mime']);
    		$info['isAnimated'] = \Local\Library\Thumb::isAnimated($file);
    		
	        $watermark = new self($file, $info);
	        
	        if(!$watermark) return;
	        
	        $watermark_width = $watermark->info['width'];
	        $watermark_height = $watermark->info['height'];
	        
	//        $watermark->image = $this->transparency($watermark, imagecreatetruecolor($watermark_width, $watermark_height));
	//        $this->image = $this->transparency($this, imagecreatetruecolor($this->info['width'], $this->info['height']));
	        
	        switch($position) {
	            case 'topleft':
	                $watermark_pos_x = 0;
	                $watermark_pos_y = 0;
	                imagecopy($this->image, $watermark->image, $watermark_pos_x, $watermark_pos_y, 0, 0, $watermark_width, $watermark_height);
	                break;
	            case 'topright':
	                $watermark_pos_x = $this->info['width'] - $watermark_width;
	                $watermark_pos_y = 0;
	                imagecopy($this->image, $watermark->image, $watermark_pos_x, $watermark_pos_y, 0, 0, $watermark_width, $watermark_height);
	                break;
	            case 'bottomleft':
	                $watermark_pos_x = 0;
	                $watermark_pos_y = $this->info['height'] - $watermark_height;
	                imagecopy($this->image, $watermark->image, $watermark_pos_x, $watermark_pos_y, 0, 0, $watermark_width, $watermark_height);
	                break;
	            case 'bottomright':
	                $watermark_pos_x = $this->info['width'] - $watermark_width;
	                $watermark_pos_y = $this->info['height'] - $watermark_height;
	                imagecopy($this->image, $watermark->image, $watermark_pos_x, $watermark_pos_y, 0, 0, $watermark_width, $watermark_height);
	                break;
	            default:
		            $padX = 40;
		            $padY = 30;
		            $wrep = ceil($this->info['width'] / $watermark_width);
		            $hrep = ceil($this->info['height'] / $watermark_height);
		            $positions = array();
		            for ($i = 0; $i <= $wrep; $i ++) {
		                for ($j = 0; $j <= $hrep; $j ++) {
		                    $positions[(($watermark_width + $padX) * $i) . '_' . (($watermark_height + $padY) * $j)] = array( (($watermark_width + $padX) * $i), (($watermark_height + $padY) * $j) );
		                }
		            }
		            foreach ($positions as $key => $data) {
		                imagecopy($this->image, $watermark->image, $data[0], $data[1], 0, 0, $watermark_width, $watermark_height);
		            }
	                break;
	        }
	        
	        imagedestroy($watermark->image);
    	}
    }
    
    public function crop($top_x, $top_y, $bottom_x, $bottom_y) {
        $image_old = $this->image;
        $this->image = imagecreatetruecolor($bottom_x - $top_x, $bottom_y - $top_y);
        
        $this->image = $this->transparency($this, imagecreatetruecolor($bottom_x - $top_x, $bottom_y - $top_y));
        
        imagecopy($this->image, $image_old, 0, 0, $top_x, $top_y, $this->info['width'], $this->info['height']);
        imagedestroy($image_old);
        
        $this->info['width'] = $bottom_x - $top_x;
        $this->info['height'] = $bottom_y - $top_y;
    }
    
    public function resize_crop($width, $height) {
        if (!$this->info['width'] || !$this->info['height']) {
            return;
        }

        $source_aspect_ratio = $this->info['width'] / $this->info['height'];
        $desired_aspect_ratio = $width / $height;

        if ( $source_aspect_ratio > $desired_aspect_ratio ) {
            $temp_height = $height;
            $temp_width = ceil( $height * $source_aspect_ratio );
        } elseif($source_aspect_ratio == $desired_aspect_ratio) {
            $temp_width = $width;
            $temp_height = $height;
        } else {
            $temp_width = $width;
            $temp_height = ceil( $width / $source_aspect_ratio );
        }

        $this->resize($temp_width, $temp_height);

        $x0 = ceil(( $temp_width - $width ) / 2);
        $y0 = ceil(( $temp_height - $height ) / 2);
        
        $this->crop($x0, $y0, ($x0 + $width), ($y0 + $height));
        
    }
    
    public function rotate($degree, $color = 'FFFFFF') {
		$rgb = $this->html2rgb($color);
		
        $this->image = imagerotate($this->image, $degree, imagecolorallocate($this->image, $rgb[0], $rgb[1], $rgb[2]));
        
        $this->info['width'] = imagesx($this->image);
		$this->info['height'] = imagesy($this->image);
        
        $this->image = $this->transparency($this, imagecreatetruecolor($this->info['width'], $this->info['height']));
        
    }
	    
    private function filter($filter) {
        imagefilter($this->image, $filter);
    }
            
    private function text($text, $x = 0, $y = 0, $size = 5, $color = '000000') {
		$rgb = $this->html2rgb($color);
        
		imagestring($this->image, $size, $x, $y, $text, imagecolorallocate($this->image, $rgb[0], $rgb[1], $rgb[2]));
    }
    
    private function merge($file, $x = 0, $y = 0, $opacity = 100) {
    	$merge = new self($file);
		        
        imagecopymerge($this->image, $merge->image, $x, $y, 0, 0, $merge->info['width'], $merge->info['height'], $opacity);
    }
			
	private function html2rgb($color) {
		if ($color[0] == '#') {
			$color = substr($color, 1);
		}
		
		if (strlen($color) == 6) {
			list($r, $g, $b) = array($color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]);   
		} elseif (strlen($color) == 3) {
			list($r, $g, $b) = array($color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2]);    
		} else {
			return FALSE;
		}
		
		$r = hexdec($r); 
		$g = hexdec($g); 
		$b = hexdec($b);    
		
		return array($r, $g, $b);
	}	

	/**
	* function transparency (resource $original, resource $new)
	*
	* Check and aply the transparency to an image
	*
	* return resource
	*/
	public function transparency ($original, $new) { 
		if ($original->info['mime'] != 'image/png' && $original->info['mime'] != 'image/gif') {
			$transparencyIndex    = imagecolorallocate($original->image, 255, 255, 255);
			imagefill($original->image, 0, 0, $transparencyIndex);
			return $original->image;
		} else {

			$trans_index = imagecolortransparent($original->image);
	
			if ($trans_index >= 0) {
				$trans_color = imagecolorsforindex($original->image, $trans_index);
				$trans_index = imagecolorallocate($new, $trans_index['red'], $trans_index['green'], $trans_index['blue']);
	
				imagefill($new, 0, 0, $trans_index);
				imagecolortransparent($new, $trans_index);
			} else if ($original->info['mime'] == 'image/png') {
				imagealphablending($new, true);
				$colorTransparent = imagecolorallocatealpha($new, 255, 255, 255, 127);
				imagefill($new, 0, 0, $colorTransparent);
				imagesavealpha($new, true);
			} else if($original->info['mime'] == 'image/gif') {
				imagecolortransparent($new, imagecolorallocate($new, 0, 0, 0));
			}
		
		}

		return $new;
	}
	
	public function getInfo() {
		return $this->info;
	}
	
}