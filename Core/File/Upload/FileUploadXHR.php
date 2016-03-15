<?php

namespace Core\File\Upload;

class FileUploadXHR {
	public $uploadName;
	final public function Save($savePath) {
		if (false !== file_put_contents ( $savePath, fopen ( 'php://input', 'r' ) )) {
			return true;
		}
		return false;
	}
	final public function getFileName() {
		return $_GET [$this->uploadName];
	}
	final public function getFileSize() {
		if (isset ( $_SERVER ['CONTENT_LENGTH'] )) {
			return ( int ) $_SERVER ['CONTENT_LENGTH'];
		} else {
			throw new \Core\Exception ( 'Content length not supported.' );
		}
	}
}