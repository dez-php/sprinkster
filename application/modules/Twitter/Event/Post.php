<?php

namespace Twitter\Event;

class Post {

	public function toTimeline($pin_id) {
		if(\Base\Config::get('facebook_post_timeline')) {
			$pinTable = new \Pin\Pin();
			$pin = $pinTable->fetchRow(array('id = ?' => $pin_id));
			if($pin) {
				$userFbTable = new \Twitter\OauthTwitter();
				$user_info = $userFbTable->fetchRow(array('user_id = ?' => $pin->user_id));
				if($user_info && $user_info->timeline) {
					$twitter = new \Twitter\Helper\Me($user_info->oauth_token, $user_info->oauth_token_secret);
					try {
						$user = $twitter->get('account/verify_credentials');
						if(isset($user->id)) {
							$pin_url = \Core\Base\Action::getInstance()->url(array('pin_id' => $pin_id),'pin');
							$post = $twitter->post('https://api.twitter.com/1.1/statuses/update.json', array(
								'status' => $pin->title . ' => ' . $pin_url
							));
						}
					} catch (\Core\Exception $e) {  }
				}
			}
		}
	}
}