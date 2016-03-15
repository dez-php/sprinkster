<?php

namespace Local\Library;

class Live {

	private $dirImages;
	
	private $httpImages;
	
	/**
	 * @var \Core\Http\Request
	 */
	private $request;
	
	private $temp_sizes = array(
		'width' => 0,
		'height' => 0,
		'date_added' => 0
	);
	
	public function __construct() {
				
		$this->dirImages = realpath(BASE_PATH . '/' . 'uploads') . '/';
		
		if(!$this->dirImages || !file_exists($this->dirImages) || !is_dir($this->dirImages)) {
			throw new \Core\Exception('Upload folder not exist!');
		}

		$this->request = \Core\Http\Request::getInstance();
		
		$this->httpImages = 'uploads/';
		
	}
	
    public function resize($filename, $width = 0, $height = 0, $scaled = true, $crop = false) {
    	if(!$width) { $width = 1; }
    	if(!$height) { $height = 1; }
    	
    	if (!file_exists($this->dirImages . $filename) || !is_file($this->dirImages . $filename)) {
    		return;
    	}
    	
    	$old_image = $filename;
    	
    	$info = pathinfo($filename);
    	$extension = $info['extension'];
    	
    	$tmp = substr($filename, 0, strrpos($filename, '.'));
    	$filename = substr($filename, 0, strrpos($filename, '.'));
    	
    	$new_image = 'cache' . $filename . '-' . $width . 'x' . $height . ($crop?'-c':($scaled?'-s':'')) . '.' . $extension;
    	$new_image = $this->normalizePath($new_image);
		if(strpos($new_image, 'cache/') !== 0)
			$new_image = 'cache/' . $new_image;

    	if (!file_exists($this->dirImages . $new_image) || (file_exists($this->dirImages . $old_image) && filemtime($this->dirImages . $old_image) > filemtime($this->dirImages . $new_image))) {
    		$path = dirname(str_replace('../', '', $new_image));

    		if(!file_exists($this->dirImages . $path) || !is_dir($this->dirImages . $path)) {
    			mkdir($this->dirImages . $path, 0777, true);
    			chmod($this->dirImages . $path, 0777);
    		}
    		
    		$image = new \Local\Library\Thumb($this->dirImages . $old_image);

			if(function_exists('exif_read_data')) {
				$exif = @exif_read_data($this->dirImages . $old_image);
				if(isset($exif['Orientation'])) {
					switch($exif['Orientation']) {
						case 8:
							$image->rotate(90);
							break;
						case 3:
							$image->rotate(180);
							break;
						case 6:
							$image->rotate(-90);
							break;
					}
				}
			}
			
			if($crop) {
				$image->resize_crop($width, $height);
			} else {
				$image->resize($width, $height, $scaled);
			}
			
			$image->save($this->dirImages . $new_image);
    		
    	}
    	
    	return $this->request->getBaseUrl() . $this->httpImages . $new_image;
    } 
	
    public function thumb($file, $width = 0, $height = 0) {
    	if(!$width) { $width = 1; }
    	if(!$height) { $height = 1; }
    	
    	$imag_info = @getimagesize($this->dirImages . $file);
    	
    	if(!$imag_info) {
    		return;
    	}
    	
    	$scale = min($width / $imag_info[0], $height / $imag_info[1]);
    	
    	$width = round($imag_info[0] * $scale);
    	$height = round($imag_info[1] * $scale);
    	
    	if($width > $imag_info[0] || $height > $imag_info[1]) {
    		$width = $imag_info[0];
    		$height = $imag_info[1];
    	}
    	
    	return $this->resize($file, $width, $height);
    }
	
	public function resizeWidth($file, $width) {
		if(!$width) { $width = 1; }
		
		$imag_info = @getimagesize($this->dirImages . $file);
		
		if(!$imag_info) {
			return;
		}
		
		$height = round($imag_info[1] / ($imag_info[0]/$width));
		
		return $this->resize($file, $width, $height);
	}
	
	public function resizeHeight($file, $height) {
		if(!$height) { $height = 1; }
		
		$imag_info = @getimagesize($this->dirImages . $file);
		
		if(!$imag_info) {
			return;
		}
		
		$width = round($imag_info[0] / ($imag_info[1]/$height));
		
		return $this->resize($file, $width, $height);
	}
	
	public function deleteImages($file, $delete_real = true) { 
		if(file_exists($this->dirImages . $file) && is_file($this->dirImages . $file)) { 
			$ext = explode('.',$file);
			$ext = '.' . end($ext);
			$filem = str_replace($ext, '', $file);
			
			$files = glob($this->dirImages . 'cache/' . $filem . '*' . $ext);
			if(is_array($files)) {
				foreach($files AS $file_delete) {
					if(is_file($file_delete)) {
						@unlink($file_delete);
					}
				}
			}
			if($delete_real) {
				@unlink($this->dirImages . $file);
			}
		}
	}

	private function normalizePath($path)
	{
		$parts = array();// Array to build a new path from the good parts
		$path = str_replace('\\', '/', $path);// Replace backslashes with forwardslashes
		$path = preg_replace('/\/+/', '/', $path);// Combine multiple slashes into a single slash
		$segments = explode('/', $path);// Collect path segments
		$test = '';// Initialize testing variable
		foreach($segments as $segment)
		{
			if($segment != '.')
			{
				$test = array_pop($parts);
				if(is_null($test))
					$parts[] = $segment;
				else if($segment == '..')
				{
					if($test == '..')
						$parts[] = $test;

					if($test == '..' || $test == '')
						$parts[] = $segment;
				}
				else
				{
					$parts[] = $test;
					$parts[] = $segment;
				}
			}
		}
		return implode('/', $parts);
	}
	
	
}