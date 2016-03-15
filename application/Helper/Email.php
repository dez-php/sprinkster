<?php

namespace Helper;

class Email extends \Core\Mailer\Base {

	public function __construct($exceptions = false) {
		parent::__construct($exceptions);
		$this->attachmendImagesBody = false;
		if(\Base\Config::get('mail_smtp')) {
			$this->SMTPAuth = true;
			$this->IsSMTP();
			$this->Host = \Base\Config::get('mail_smtp_host');
			$this->Port = \Base\Config::get('mail_smtp_port');
			$this->Username = \Base\Config::get('mail_smtp_user');
			$this->Password = \Base\Config::get('mail_smtp_password');
		}
		$this->AltBody = "To view the message, please use an HTML compatible email viewer!"; // optional, comment out and test
	}
	
	/**
	 * @param string $title
	 * @return \Helper\Email
	 */
	public function addTitle($title = '') {
		$this->Subject = $title;
		return $this;
	}
	
	/**
	 * @param string $body
	 * @return \Helper\Email
	 */
	public function addHtml($body = '') {
		$this->MsgHTML($body, BASE_PATH);
		return $this;
	}
	
	/**
	 * @param string $address
	 * @param string $name
	 * @return \Helper\Email
	 */
	public function addTo($address, $name = '') {
		$this->AddAddress($address, $name);
		return $this;
	}
	
	/**
	 * @param string $address
	 * @param string $name
	 * @return \Helper\Email
	 */
	public function addFrom($address, $name = '') {
		$this->SetFrom($address, $name);
		$this->AddReplyTo($address, $name);
		return $this;
	}
	
	/**
	 * @return boolean
	 */
	public function send() {
		$result = parent::Send();
		if($result) {
			return true;
		} else {
			return false;
		}
	}
	
}