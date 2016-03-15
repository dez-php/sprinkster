<?php

namespace Wishlist;

use \Core\Text\String;

use \Base\Model\SearchResult;
use \Base\Model\SearchResultCollection;

class WishlistSearchProvider extends \Base\Model\SearchProvider {

	protected $ID = 'Wishlist';
	protected $label = 'Wishlists';

	public function query($query, $_ = NULL)
	{
		$grid = (new \Wishlist\Widget\Grid);
		$result = new SearchresultCollection;

		$grid->setFilter([ 'title' => $query ]);
		$grid->setLimit(self::DefaultFeedLimit);

		$wishlists = $grid->getWishlists();

		$label = $_ ? $_->toString($this->label) : NULL;

		foreach($wishlists as $wishlist)
			$result->add(new SearchResult('wishlist', $wishlist->id, String::cut($wishlist->title, 40), $wishlist->description, $this->url([ 'wishlist_id' => $wishlist->id, 'query' => $this->urlQuery($wishlist->title) ], 'wishlist'), \Wishlist\Helper\Cover::getImage('small', $wishlist), 'icon-search icon-search-wishlist', $label));

		return $result;
	}

}