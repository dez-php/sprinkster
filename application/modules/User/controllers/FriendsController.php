<?php

namespace User;

class FriendsController extends \Base\PermissionController {
	
	public function init()
	{
		$this->noLayout(true);
	}
	
	public function indexAction()
	{
		$me = \User\User::getUserData();
		$return = [];
		
		$search = $this->getRequest()->getRequest('query');
		$search = $search ?: $this->getRequest()->getRequest('value');
		
		if(!$me->id || 2 > mb_strlen($search, 'utf-8'))
			return $this->responseJsonCallback($return);

		$userTable = new \User\User();
		$search = $userTable->getAdapter()->quote($search . '%');
		
		$users = $userTable->fetchAll(
			$userTable->makeWhere([
				'where' => '(user.id IN (SELECT follow_id FROM user_follow WHERE user_id = ' . $me->id . ') OR user.id IN (SELECT follow_id FROM wishlist_follow WHERE user_id = ' . $me->id . ')) AND (username LIKE '.$search.' OR firstname LIKE '.$search.' OR lastname LIKE '.$search.')'
			]),
			NULL,
			300
		);

		foreach($users AS $user)
		{
			$avatars = [];

			try
			{
				$avatars = \User\Helper\Avatar::getImages($user);
			}
			catch(\Exception $e)
			{
			}

			$return[] = [
				'id' => $user->id,
				'name' => $user->getUserFullname(),
				'username' => $user->username,
				'avatar' => $avatars,
				'type' => 'follow',
				'url' => $this->url([ 'user_id' => $user->id, 'query' => $this->urlQuery($user->username) ], 'user'),
				'value' => $user->getUserFullname(), // 4 Autocomplete
			];
		}

		$this->responseJsonCallback($return);
	}
	
}