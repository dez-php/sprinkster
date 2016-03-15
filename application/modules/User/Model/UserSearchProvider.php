<?php

namespace User;

use \Core\Text\String;

use \Base\Model\SearchResult;
use \Base\Model\SearchResultCollection;

class UserSearchProvider extends \Base\Model\SearchProvider {

	protected $ID = 'User';
	protected $label = 'Users';

	public function query($query, $_ = NULL)
	{
		$grid = (new \User\Widget\Grid);
		$result = new SearchresultCollection;

		$grid->setFilter([ 'query' => $query ]);
		$grid->setLimit(self::DefaultFeedLimit);

		$users = $grid->getUsers();

		$label = $_ ? $_->toString($this->label) : NULL;

		foreach($users as $user)
			$result->add(new SearchResult('user', $user->id, String::cut($user->username, 40), $user->getUserFullName(), $this->url([ 'user_id' => $user->id, 'query' => $this->urlQuery($user->username) ], 'user'), \User\Helper\Avatar::getImage('small', $user), 'icon-search icon-search-user', $label));

		return $result;
	}

}