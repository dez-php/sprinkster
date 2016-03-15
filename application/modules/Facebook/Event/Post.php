<?php

namespace Facebook\Event;

class Post {

	public function toTimeline($pin_id) {
		if(\Base\Config::get('facebook_post_timeline') && \Base\Config::get('facebook_status')) {
			$pinTable = new \Pin\Pin();
			$pin = $pinTable->fetchRow(array('id = ?' => $pin_id));
			if($pin) {
				$userFbTable = new \Facebook\OauthFacebook();
				$user_info = $userFbTable->fetchRow(array('user_id = ?' => $pin->user_id));
				if($user_info && $user_info->timeline) {
					$facebook = new \Facebook\Helper\Me();
					$facebook->setAccessToken($user_info->access_token);
					try {
						$perm = $facebook->getPermisions('publish_actions');
						if(!$perm)
							return;
						
						$facebook_user_info = $facebook->getUser();
						if(isset($facebook_user_info['id'])) {
							$pin_url = \Core\Base\Action::getInstance()->url(array('pin_id' => $pin_id),'pin');
							$params = array('message'=>$pin_url);
							$response = $facebook->post($user_info->facebook_id . '/feed', $params, $facebook_user_info['accessToken']);
							if(\Base\Config::get('facebook_og_namespace') && \Base\Config::get('facebook_og_action')) {
								$params = array(\Base\Config::get('facebook_og_action')=>$pin_url,'access_token'=>$facebook->getAccessToken());
								$response1 = $facebook->facebook->api($facebook_user_info['id'] . '/'.\Base\Config::get('facebook_og_namespace').':'.\Base\Config::get('facebook_og_action'),'post',$params);
							}
						}
					} catch (\Core\Exception $e) { }
				}
			}
		}
	}
}