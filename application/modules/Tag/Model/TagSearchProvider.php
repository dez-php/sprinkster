<?php

namespace Tag;

use \Core\Text\String;

use \Base\Model\SearchResult;
use \Base\Model\SearchResultCollection;

class TagSearchProvider extends \Base\Model\SearchProvider {

	protected $ID = 'Tag';
	protected $label = 'Items';

	public function query($query, $_ = NULL)
	{
		$db = \Core\Db\Init::getDefaultAdapter();
		$grid = (new \Pin\Widget\Grid);
		$result = new SearchresultCollection;

		$wordsAll = new \Core\Text\ParseWords($query);
		$words = $wordsAll->getMinLenght(1);
		$quoted = [];

		foreach($words as $word)
			$quoted[] = 'tag LIKE ' . $db->quote($word . '%');

		$fulltext = \Base\Config::get('database_fulltext_status') && version_compare((new \Pin\Pin())->getAdapter()->getServerVersion(), '5.6', '>=');
		$sql = 'SELECT t.id FROM tag t WHERE ' . implode(' OR ', $quoted) . ' LIMIT ' . self::DefaultFeedLimit;

		$ids = $db->fetchCol($sql);

		array_walk($ids, 'intval');

		if(empty($ids))
			$ids[] = 0;

		if(\Core\Base\Action::getInstance()->isModuleAccessible('Store')) {
			$grid->setFilter([ 'callback' => [ 'id' => ['\Tag\PinTag::pinTagsCallback(' . implode(', ', $ids) . ')', '\Store\PurchasedStoreItem::inStock(FALSE)'] ] ]);
		} else {
			$grid->setFilter([ 'callback' => [ 'id' => ['\Tag\PinTag::pinTagsCallback(' . implode(', ', $ids) . ')'] ] ]);
		}
		
		$grid->setLimit(self::DefaultFeedLimit);
		
		$pins = $grid->getPins();

		foreach($pins as $pin)
		{
			$icon = 'icon-search ' . ($pin->product ? 'icon-search-item' : 'icon-search-pin');
			$label = $_ ? $_->toString($pin->product ? 'Items' : 'Pins') : NULL;

			if($pin->product && ($price = (new \Store\PinQuantity)->fetchRow([ 'pin_id = ?' => (int) $pin->id ], 'id ASC'))) {
				$price =  \Currency\Helper\Format::format_convert($price->price, $pin->currency_code, \Currency\Helper\Format::setCode());
			} else {
				$price = $pin->product == 1 ? \Currency\Helper\Format::format_convert($price->price, $pin->currency_code, \Currency\Helper\Format::setCode()) : NULL;
			}

//			$price = $pin->product ? \Currency\Helper\Format::format((new \Store\PinQuantity)->fetchRow([ 'pin_id = ?' => (int) $pin->id ], 'id ASC'), \Store\Settings::getCurrencyCode($pin->user_id)) : NULL;

				
			if($price) {
				$price = ' - ' . $price;
			}
			
			$result->add(new SearchResult('tag', $pin->id, String::cut($pin->title, 30) . $price, $pin->description, $this->url([ 'pin_id' => $pin->id, 'query' => $this->urlQuery($pin->title) ], 'pin'), \Pin\Helper\Image::getImage('small', $pin), $icon, $label, TRUE, 'pin'));
		}

		return $result;
	}

}