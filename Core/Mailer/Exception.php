<?php

namespace Core\Mailer;

class Exception extends \Core\Exception {
	public function errorMessage() {
		$errorMsg = '<strong>' . $this->getMessage () . "</strong><br />\n";
		return $errorMsg;
	}
}

?>