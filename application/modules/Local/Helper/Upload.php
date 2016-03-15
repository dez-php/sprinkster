<?php

namespace Local\Helper;

class Upload extends \Local\Helper\AbstractUpload {
	
	/*public function __construct($sizes) {
		parent::__construct($sizes);
		$this->_ = new \Translate\Locale('Front\Upload\Local', \Core\Base\Action::getModule('Language')->getLanguageId());
	}*/
	
	/**
	 * @var disable rename if self
	 */
	private $rename = true;
	
	/* (non-PHPdoc)
	 * @see \Local\Helper\AbstractUpload::_upload()
	 */
	protected function _upload($image, $image_path, $config) {
		if(!$this->image_info) { 
			$imageSizeObject = new \Core\Image\Getimagesize($image);
			$this->image_info = $imageSizeObject->getSize(); 
		}

		if($this->image_info) {
			$this->image_info['store'] = 'Local';
			$this->image_info['store_host'] = '{local}';
			$this->image_info['width'] = $this->image_info[0];
			$this->image_info['height'] = $this->image_info[1];
			$image_path = '/' . trim($image_path, '/') . '/';
			if (!file_exists(BASE_PATH . '/uploads' . $image_path) || !is_dir(BASE_PATH . '/uploads' . $image_path)) {
				@mkdir(BASE_PATH . '/uploads' . $image_path, 0777, true);
				@chmod(BASE_PATH . '/uploads' . $image_path, 0777);
			}
			if(!file_exists(BASE_PATH . '/uploads' . $image_path)) { 
				$this->error = $this->_('Unable to create image path!');
				return false; 
			}

			if(in_array($this->image_info['mime'], $this->allowedMimeImages)) {
				$ext = \Core\File\Ext::getExtFromMime($this->image_info['mime']);
				if(isset($this->image_info['file']) && $this->image_info['file']) {
					$name = basename($this->image_info['file']);
				} else {
					$name = md5(basename($image) . mt_rand(0, time()) . microtime()) . '.' . $ext;
					$name = $this->rename_if_exists(BASE_PATH . '/uploads' . $image_path, $name);
				}
				if(!file_exists(BASE_PATH . '/uploads' . $image_path . $name)) {
					if(!@copy($image, BASE_PATH . '/uploads' . $image_path . $name)) {
						$this->error = $this->_('Unable to get image!');
						return false;
					} else {
						$this->image_info['file'] = '/uploads' . $image_path . $name;
						/*if($this->max_width < $this->image_info['width']) {
							$this->config = $config;
							$width = $this->getWidth();

							$thumb = new \Local\Library\Thumb(BASE_PATH . '/uploads' . $image_path . $name);
							//$thumb->resizeWidth($width);
							$save = $thumb->save(BASE_PATH . '/uploads' . $image_path . $name);

							$info = $thumb->getInfo('width');
							$this->image_info[0] = $info['width'];
							$this->image_info[1] = $info['height'];
						}*/
					}
				}
				
				$thumb = new \Local\Library\Thumb(BASE_PATH . '/uploads' . $image_path . $name);
				
				if($config['width'] && $config['height']) {
					if($config['thumb']) {
						$thumb->thumb($config['width'], $config['height']);
					} else if($config['crop']) {
						$thumb->resize_crop($config['width'], $config['height']);
					} else {
						$thumb->resize($config['width'], $config['height']);
					}
				} else if($config['width'] && !$config['height']) {
					if($config['width'] != $this->image_info['width']) {
						$thumb->resizeWidth($config['width']);
					}
				} else if(!$config['width'] && $config['height']) {
					$thumb->resizeHeight($config['height']);
				} else {
					$this->error = $this->_('Image dimensions is not defined!');
					return false;
				}
				
				if(!file_exists(BASE_PATH . '/uploads' . $image_path . $config['folder'] . '/') || !is_dir(BASE_PATH . '/uploads' . $image_path . $config['folder'] . '/')) {
					@mkdir(BASE_PATH . '/uploads' . $image_path . $config['folder'] . '/', 0777, true);
				}
				
				if($this->_watermark && file_exists(BASE_PATH . '/uploads/data/' . $this->_watermark)) {
					$thumb->watermark(BASE_PATH . '/uploads/data/' . $this->_watermark, $this->_watermark_position);
				}
				
				if(!($this->_watermark && file_exists(BASE_PATH . '/uploads/data/' . $this->_watermark)) && $config['width'] && !$config['height'] && $config['width'] == $this->image_info['width']) {
					if(@copy(BASE_PATH . '/uploads' . $image_path . $name, BASE_PATH . '/uploads' . $image_path . $config['folder'] . '/' . $name)) {
						return true;
					} else {
						$this->error = $this->_('Unable to save image!');
						return false;
					}
				} else {
					if($thumb->save(BASE_PATH . '/uploads' . $image_path . $config['folder'] . '/' . $name)) {
						return true;
					} else {
						$this->error = $this->_('Unable to save image!');
						return false;
					}
				}
				
			} else {
				$this->error = $this->_('This image type is not allowed to upload!');
				return false;
			}
		} else {
			$this->error = $this->_('Unable to get image info!');
			return false;
		}
	}

	/* (non-PHPdoc)
	 * @see \Local\Helper\AbstractUpload::_upload()
	 */
	protected function _uploadFile($file, $file_path) {

		$this->file_info['store'] = 'Local';
		$this->file_info['store_host'] = '{local}';
		$file_path = '/' . trim($file_path, '/') . '/files/';
		if (!file_exists(BASE_PATH . '/uploads' . $file_path) || !is_dir(BASE_PATH . '/uploads' . $file_path)) {
			@mkdir(BASE_PATH . '/uploads' . $file_path, 0777, true);
			@chmod(BASE_PATH . '/uploads' . $file_path, 0777);
		}
		if(!file_exists(BASE_PATH . '/uploads' . $file_path)) {
			$this->error = $this->_('Unable to create file path!');
			return false;
		}

		$ext = pathinfo($file, PATHINFO_EXTENSION);
		if(isset($this->file_info['file']) && $this->file_info['file']) {
			$name = basename($this->file_info['file']);
		} else {
			$name = md5(basename($file) . mt_rand(0, time()) . microtime()) . '.' . $ext;
			$name = $this->rename_if_exists(BASE_PATH . '/uploads' . $file_path, $name);
		}

		if(!@copy($file, BASE_PATH . '/uploads' . $file_path . $name)) {
			$this->error = $this->_('Unable to upload file!');
			return false;
		}
		$this->file_info['file'] = '/uploads' . $file_path . $name;
		return true;

	}
	
	protected function _delete($file, $config, $deleteOriginalOnly = false) {
		$file = $file ? $file : ( isset($this->image_info['file'])?$this->image_info['file']:'' );
		if($file) {
			$path = dirname($file);
			$filename = basename($file);
			if(file_exists(BASE_PATH . $file)) {
				@unlink(BASE_PATH . $file);
			}
			if(!$deleteOriginalOnly && file_exists(BASE_PATH . $path . DIRECTORY_SEPARATOR . $config['folder'] . DIRECTORY_SEPARATOR . $filename)) {
				@unlink(BASE_PATH . $path . DIRECTORY_SEPARATOR . $config['folder'] . DIRECTORY_SEPARATOR . $filename);
			}
		}
		return true;
	}

	protected function _deleteFile($file) {
		$file = $file ? $file : ( isset($this->file_info['file'])?$this->file_info['file']:'' );
		if($file) {
			if(file_exists(BASE_PATH . $file))
				@unlink(BASE_PATH . $file);
		}
		return true;
	}

    /**
     * @param string $dir
     * @param string $filename
     * @return string
     */
    private function rename_if_exists($dir, $filename) {
    	if($this->rename) {
	        $ext = strtolower(strrchr($filename, '.'));
	        $prefix = substr($filename, 0, -strlen($ext));
	        $i = 0;
	        while (file_exists($dir . $filename)) { // If file exists, add a number to it.
	            $filename = $prefix . '[' . ++$i . ']' . $ext;
	        }
    	}
    	$this->rename = false;
        return $filename;
    }
	
}