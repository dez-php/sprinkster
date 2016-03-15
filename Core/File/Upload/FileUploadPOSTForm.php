<?php

namespace Core\File\Upload;

final class FileUploadPOSTForm {
	public $uploadName;
	final public function Save($savePath) {
		if (move_uploaded_file ( $_FILES [$this->uploadName] ['tmp_name'], $savePath )) {
			return true;
		}
		return false;
	}
	final public function getFileName() {
		return $_FILES [$this->uploadName] ['name'];
	}
	final public function getFileSize() {
		return $_FILES [$this->uploadName] ['size'];
	}
}