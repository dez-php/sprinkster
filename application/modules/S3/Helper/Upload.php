<?php

namespace S3\Helper;

use Aws\S3;

class Upload extends \Local\Helper\AbstractUpload {
	
	private $s3;

	/**
	 * @var disable rename if self
	 */
	private $rename = true;
	
	public function __construct($module, $sizes) {
		parent::__construct($module, $sizes);
		$this->_ = new \Translate\Locale('Front\Upload\S3', \Core\Base\Action::getModule('Language')->getLanguageId());
		
		try {
			$this->s3 = new \Aws\S3(
				\Base\Config::get('s3_access_key'),
				\Base\Config::get('s3_secret_key'),
				\Base\Config::get('s3_ssl') ? true : false		
			);
			$this->s3->setExceptions(true);
			if($this->s3->hasAuth()) {
				if(!$this->s3->getBucketLogging(\Base\Config::get('s3_bucklet'))) {
					$this->error = $this->_('Unable to connect to upload server!');
				}
			} else {
				$this->error = $this->_('Error authentication to Aws S3 failed.');
			}
		} catch (\Core\Exception $e) {
			$this->error = $e->getMessage();
		}
	}
	
	protected function _upload($image, $image_path, $config) {
		if(!$this->error) {
			if(!$this->image_info) {
				$imageSizeObject = new \Core\Image\Getimagesize($image);
				$this->image_info = $imageSizeObject->getSize();
			}
			if($this->image_info) {
				$this->image_info['store'] = 'S3';
				$this->image_info['store_host'] = trim(\Base\Config::get('s3_bucklet_location'),'/');
				$this->image_info['width'] = $this->image_info[0];
				$this->image_info['height'] = $this->image_info[1];
				$image_path = '/' . trim($image_path, '/') . '/';
				if (!file_exists(BASE_PATH . '/cache/uploads' . $image_path) || !is_dir(BASE_PATH . '/cache/uploads' . $image_path)) {
					@mkdir(BASE_PATH . '/cache/uploads' . $image_path, 0777, true);
					@chmod(BASE_PATH . '/cache/uploads' . $image_path, 0777);
				}
				if(!file_exists(BASE_PATH . '/cache/uploads' . $image_path)) {
					$this->error = $this->_('Unable to create image path!');
					return false;
				}
				if(in_array($this->image_info['mime'], $this->allowedMimeImages)) {
					$ext = \Core\File\Ext::getExtFromMime($this->image_info['mime']);
					if(isset($this->image_info['file']) && $this->image_info['file']) {
						$name = basename($this->image_info['file']);
					} else {
						$name = md5(basename($image) . mt_rand(0, time()) . microtime()) . '.' . $ext;
						$name = $this->rename_if_exists($image_path, $name);
					}
					if(!file_exists(BASE_PATH . '/cache/uploads' . $image_path . $name)) {
						if(!@copy($image, BASE_PATH . '/cache/uploads' . $image_path . $name)) {
							$this->error = $this->_('Unable to get image!');
							return false;
						} else {
							$this->image_info['file'] = $image_path . $name;
							/*if($this->max_width < $this->image_info['width']) {
								$thumb = new \Local\Library\Thumb(BASE_PATH . '/cache/uploads' . $image_path . $name);
								//$thumb->resizeWidth($this->max_width);
								$save = $thumb->save(BASE_PATH . '/cache/uploads' . $image_path . $name);
								$info = $thumb->getInfo('width');
								$this->image_info[0] = $info['width'];
								$this->image_info[1] = $info['height'];
							}*/
						}
					}
			
					$thumb = new \Local\Library\Thumb(BASE_PATH . '/cache/uploads' . $image_path . $name);
			
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
			
					if(!file_exists(BASE_PATH . '/cache/uploads' . $image_path . $config['folder'] . '/') || !is_dir(BASE_PATH . '/cache/uploads' . $image_path . $config['folder'] . '/')) {
						@mkdir(BASE_PATH . '/cache/uploads' . $image_path . $config['folder'] . '/', 0777, true);
					}
				
					if($this->_watermark && file_exists(BASE_PATH . '/uploads/data/' . $this->_watermark)) {
						$thumb->watermark(BASE_PATH . '/uploads/data/' . $this->_watermark, $this->_watermark_position);
					}
			
					if(!($this->_watermark && file_exists(BASE_PATH . '/uploads/data/' . $this->_watermark)) && $config['width'] && !$config['height'] && $config['width'] == $this->image_info['width']) {
						if(@copy(BASE_PATH . '/cache/uploads' . $image_path . $name, BASE_PATH . '/cache/uploads' . $image_path . $config['folder'] . '/' . $name)) {
							if($this->uploadS3(BASE_PATH . '/cache/uploads' . $image_path . $config['folder'] . '/' . $name, $image_path . $config['folder'] . '/' . $name)) {
								@unlink(BASE_PATH . '/cache/uploads' . $image_path . $config['folder'] . '/' . $name);
								return true;
							}
							return false;
						} else {
							$this->error = $this->_('Unable to save image!');
							return false;
						}
					} else {
						if($thumb->save(BASE_PATH . '/cache/uploads' . $image_path . $config['folder'] . '/' . $name)) {
							if($this->uploadS3(BASE_PATH . '/cache/uploads' . $image_path . $config['folder'] . '/' . $name, $image_path . $config['folder'] . '/' . $name)) {
								@unlink(BASE_PATH . '/cache/uploads' . $image_path . $config['folder'] . '/' . $name);
								return true;
							} else {
								$this->error = $this->_('Unable to save image on Aws S3!');
							}
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
		return false;
	}

	protected function _uploadFile($file, $file_path) {

		$this->file_info['store'] = 'S3';
		$this->file_info['store_host'] = trim(\Base\Config::get('s3_bucklet_location'),'/');
		$file_path = '/' . trim($file_path, '/') . '/files/';

		$ext = pathinfo($file, PATHINFO_EXTENSION);
		if(isset($this->file_info['file']) && $this->file_info['file']) {
			$name = basename($this->file_info['file']);
		} else {
			$name = md5(basename($file) . mt_rand(0, time()) . microtime()) . '.' . $ext;
			$name = $this->rename_if_exists($file_path, $name);
		}

		if($this->uploadS3($file, $file_path . $name)) {
			$this->file_info['file'] = $file_path . $name;
			return true;
		}
		$this->error = $this->_('Unable to upload file on Aws S3!');
		return false;
	}
	
	protected function _delete($file, $config, $deleteOriginalOnly = false) {
		$file = $file ? $file : ( isset($this->image_info['file'])?$this->image_info['file']:'' );
		if($file) {
			$path = dirname($file);
			$filename = basename($file);
			if(file_exists(BASE_PATH . '/cache/uploads' . $file)) {
				@unlink(BASE_PATH . '/cache/uploads' . $file);
			}
			if(!$deleteOriginalOnly) {
				$this->deleteS3($path . '/' . $config['folder'] . '/' . $filename);
			}
		}
		return true;
	}

	protected function _deleteFile($file) {
		$file = $file ? $file : ( isset($this->file_info['file'])?$this->file_info['file']:'' );
		if($file) {
			$this->deleteS3($file);
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
	        while ($this->s3->getObjectInfo(\Base\Config::get('s3_bucklet'), $dir . $filename) ) { // If file exists, add a number to it.
	            $filename = $prefix . '[' . ++$i . ']' . $ext;
	        }
    	}
    	$this->rename = false;
        return $filename;
    }
	
	//////////////////////////////////////////
	
	public function uploadS3($image, $image_server) {
		try {
			if( !$this->error ) {
				if ( !$this->s3->putObjectFile($image, \Base\Config::get('s3_bucklet'), ltrim($image_server, '/'), \Aws\S3::ACL_PUBLIC_READ, array(), \Core\File\Ext::getMimeFromFile($image)) ) {
					$this->error = $this->_('Unable to upload to the server!');
				} else {
					return true;
				}
			}
			return false;
		} catch (\Core\Exception $e) {
			$this->error = $e->getMessage();
			return false;
		}
	}
	
	public function deleteS3($image) {
		$image = ltrim($image,'/');
		try {
			return $this->s3->deleteObject(\Base\Config::get('s3_bucklet'), $image);
		} catch (\Core\Exception $e) {
			$this->error = $e->getMessage();
			return false;
		}
	}
	
}