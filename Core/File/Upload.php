<?php

namespace Core\File;

class Upload {
	public $uploadDir; // File upload directory (include trailing slash)
	public $allowedExtensions; // Array of permitted file extensions
	public $sizeLimit = 1048576; // Max file upload size in bytes (default 1MB)
	public $newFileName; // Optionally save uploaded files with a new name by
	public $enableRotateMobile = true;
	                     // setting this
	private $fileName; // Filename of the uploaded file
	private $fileSize; // Size of uploaded file in bytes
	private $fileExtension; // File extension of uploaded file
	private $savedFile; // Path to newly uploaded file (after upload completed)
	private $errorMsg; // Error message if handleUpload() returns false (use
	                   // getErrorMsg() to retrieve)
	
	private $handler;

	function __construct($uploadName) {
		if (isset ( $_FILES [$uploadName] )) {
			$this->handler = new \Core\File\Upload\FileUploadPOSTForm (); // Form-based
			                                                              // upload
		} elseif (isset ( $_GET [$uploadName] )) {
			$this->handler = new \Core\File\Upload\FileUploadXHR (); // XHR upload
		} else {
			$this->handler = false;
		}

		$this->sizeLimit = $this->file_upload_max_size();
		if($this->sizeLimit < 0)
			$this->sizeLimit = 1048576;
		
		if ($this->handler) {
			$this->handler->uploadName = $uploadName;
			$this->fileName = $this->handler->getFileName ();
			$this->fileSize = $this->handler->getFileSize ();
			$fileInfo = pathinfo ( $this->fileName );
			if (array_key_exists ( 'extension', $fileInfo )) {
				$this->fileExtension = strtolower ( $fileInfo ['extension'] );
			}
		}
	}
	public function getFileName() {
		return $this->fileName;
	}
	public function getFileSize() {
		return $this->fileSize;
	}
	public function getExtension() {
		return $this->fileExtension;
	}
	public function getErrorMsg() {
		return $this->errorMsg;
	}
	public function getSavedFile() {
		return $this->savedFile;
	}
	private function checkExtension($ext, $allowedExtensions) {
		if (! is_array ( $allowedExtensions )) {
                        return false;
		}
		if (! in_array ( strtolower ( $ext ), array_map ( 'strtolower', $allowedExtensions ) )) {
			return false;
		}
		return true;
	}
	private function setErrorMsg($msg) {
		$this->errorMsg = $msg;
	}
	private function fixDir($dir) {
		$last = substr ( $dir, - 1 );
		if ($last == '/' || $last == '\\') {
			$dir = substr ( $dir, 0, - 1 );
		}
		return $dir . DIRECTORY_SEPARATOR;
	}
	public function handleUpload($uploadDir = null, $allowedExtensions = null) {
		if ($this->handler === false) {
			$this->setErrorMsg ( 'Incorrect upload name or no file uploaded' );
			return false;
		}
		
		if (! empty ( $uploadDir )) {
			$this->uploadDir = $uploadDir;
		}
		if (is_array ( $allowedExtensions )) {
			$this->allowedExtensions = $allowedExtensions;
		}
		
		$this->uploadDir = $this->fixDir ( $this->uploadDir );
		
		if (! empty ( $this->newFileName )) {
			$this->fileName = $this->newFileName;
			$this->savedFile = $this->uploadDir . $this->newFileName;
		} else {
			$this->savedFile = $this->uploadDir . $this->fileName;
		}
		
		if ($this->fileSize == 0) {
			$this->setErrorMsg ( 'File is empty' );
			return false;
		}
		if (! is_writable ( $this->uploadDir )) {
			$this->setErrorMsg ( 'Upload directory is not writable' );
			return false;
		}
		if ($this->fileSize > $this->sizeLimit) {
			$this->setErrorMsg ( 'File size exceeds limit' );
			return false;
		}
		if (! empty ( $this->allowedExtensions )) {
			if (! $this->checkExtension ( $this->fileExtension, $this->allowedExtensions )) {
				$this->setErrorMsg ( 'Invalid file type' );
				return false;
			}
		}
		if (! $this->handler->Save ( $this->savedFile )) {
			$this->setErrorMsg ( 'File could not be saved' );
			return false;
		}
		
		if($this->enableRotateMobile && $this->fileExtension && in_array($this->fileExtension, ['jpg', 'jpeg'])) {
			$this->rotateImage($this->savedFile);
		}
		
		return true;
	}
	
	private function rotateImage($imagePath) {
		if(function_exists('exif_read_data')) {
			$exif = @exif_read_data($imagePath);
			if(isset($exif['Orientation'])) {
				$image = @imagecreatefromjpeg($imagePath);
				if(!$image)
					return;
				$rotate = false;
				switch($exif['Orientation']) {
					case 8:
						$image = imagerotate($image,90,0);
						$rotate = true;
					break;
					case 3:
						$image = imagerotate($image,180,0);
						$rotate = true;
					break;
					case 6:
						$image = imagerotate($image,-90,0);
						$rotate = true;
					break;
				}
				if($rotate)
					imagejpeg($image, $imagePath);
			}
		}
	}

	private function file_upload_max_size() {
		static $max_size = -1;

		if ($max_size < 0) {
			// Start with post_max_size.
			$max_size = $this->parse_size(ini_get('post_max_size'));

			// If upload_max_size is less, then reduce. Except if upload_max_size is
			// zero, which indicates no limit.
			$upload_max = $this->parse_size(ini_get('upload_max_filesize'));
			if ($upload_max > 0 && $upload_max < $max_size) {
				$max_size = $upload_max;
			}
		}
		return $max_size < 0 ? $max_size : ($max_size - 1);
	}

	private function parse_size($size) {
		$unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
		$size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
		if ($unit) {
			// Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
			return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
		}
		else {
			return round($size);
		}
	}
	
}