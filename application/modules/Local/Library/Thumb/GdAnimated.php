<?php

namespace Local\Library\Thumb;

include_once __DIR__ . '/gdenhancer/models/Actions.php';

class GdAnimated {
	
	private $file;
    /**
     * @var \gdenhancer\models\Actions
     */
    private $image;
    private $info;
		
	public function __construct($file, $info) {
		set_time_limit(0);
		ini_set('memory_limit', '2048M');
		$this->file = $file;

		$this->info = array(
			'width'  => $info[0],
			'height' => $info[1],
			'bits'   => $info['bits'],
			'mime'   => $info['mime'],
			'extension' => $info['extension'],
			'isAnimated' => $info['isAnimated']
		);
        	
		$this->image = new \gdenhancer\models\Actions($file, $this->info);
	}
	
	public function gray() {}
	
    public function save($file, $gray = false, $quality = 75, $destroy = true) {
    	$this->image->saveFormat('gif');
    	$this->image->GIFFlag(true);
    	include_once __DIR__ . '/gdenhancer/models/Run.php';
    	$run = new \gdenhancer\models\Run($this->image);
    	return @file_put_contents($file, $run->save['contents']);
    }	
	
    public function show() {
    	$this->image->saveFormat('gif');
    	$this->image->GIFFlag(true);
    	include_once __DIR__ . '/gdenhancer/models/Run.php';
    	$run = new \gdenhancer\models\Run($this->image);
    	
        header("Content-Type: image/gif");
        echo $run->save['contents'];
        exit;
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
		
    	$this->image->backgroundResize($width, $height, 'shrink'); //option fill
        $this->info['width']  = $width;
        $this->info['height'] = $height;
    }
	
    public function resize($width = 0, $height = 0, $scaled = true) {
    	$this->thumb($width, $height);
    }
    
    public function watermark($file, $position = 'bottomright') { }
    
    public function crop($top_x, $top_y, $bottom_x, $bottom_y) { }
    
    public function resize_crop($width, $height) {
        $this->image->backgroundResize($width, $height, 'fill'); //option fill
        $this->info['width']  = $width;
        $this->info['height'] = $height;
    }
    
    public function rotate($degree, $color = 'FFFFFF') { }
	    
    private function filter($filter) { }
            
    private function text($text, $x = 0, $y = 0, $size = 5, $color = '000000') { }
    
    private function merge($file, $x = 0, $y = 0, $opacity = 100) { }
			
	private function html2rgb($color) { }
	
	public function getInfo() {
		return $this->info;
	}
	
}