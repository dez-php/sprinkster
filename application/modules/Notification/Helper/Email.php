<?php

namespace Notification\Helper;

class Email {

	public static function result($group, $data)
	{
		if(!$group || !isset($data['email']) || !$data['email'])
			return;

		////// send notification
		$NotificationTable = new \Notification\Notification();
		if(isset($data['language_id'])) { 
			$Notification = $NotificationTable->setLanguageId($data['language_id']);
		}
		$Notification = $Notification->setReplace($data)->get($group);

		$result = null;
		if($Notification)
		{
			$email = new \Helper\Email();
			$email->addFrom(\Base\Config::get('no_reply'));
			$email->addTo($data['email'], isset($data['fullname'])?$data['fullname']:null);
			$email->addTitle($Notification->title);
			$email->addHtml($Notification->description);
			$result = $email->send();
		}
		
		return $result;
		
	}
}