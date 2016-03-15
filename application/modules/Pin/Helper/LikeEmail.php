<?php

namespace Pin\Helper;

class LikeEmail {

	public static function result($user, $pin_id, $pin_url, $author_url)
	{
		if(count($user) > array_filter($user))
			return;

		if(!isset($user['notify']) || !$user['notify'])
			return;

		$id = isset($user['id']) ? $user['id'] : NULL;
		$email_address = isset($user['email']) ? $user['email'] : NULL;
		$language_id = isset($user['language_id']) ? $user['language_id'] : NULL;
		$firstname = isset($user['firstname']) ? $user['firstname'] : NULL;
		$lastname = isset($user['lastname']) ? $user['lastname'] : NULL;
		$username = isset($user['username']) ? $user['username'] : NULL;
		$fullname = isset($user['fullname']) ? $user['fullname'] : NULL;
		$fullname_author = isset($user['author_fullname']) ? $user['author_fullname'] : NULL;

		////// send notification
		$NotificationTable = new \Notification\Notification();
		$Notification = $NotificationTable->setLanguageId($language_id)->setReplace([
				'user_id' => (int) $id,
				'user_firstname' => $firstname,
				'user_lastname' => $lastname,
				'user_username' => $username,
				'user_fullname' => $fullname,
				'pin_url' => $pin_url,
				'author_url' => $author_url,
				'author_fullname' => $fullname_author
		])->get('like_pin');

		if($Notification)
		{
			$email = new \Helper\Email();
			$email->addFrom(\Base\Config::get('no_reply'));
			$email->addTo($email_address, $fullname);
			$email->addTitle($Notification->title);
			$email->addHtml($Notification->description);
			$email->send();
		}
	}
}