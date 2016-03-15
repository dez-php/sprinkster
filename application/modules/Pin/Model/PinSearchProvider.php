<?php

namespace Pin;

use \Core\Http\Url;
use \Core\Text\String;

use \Base\Model\SearchResult;
use \Base\Model\SearchResultCollection;

class PinSearchProvider extends \Base\Model\SearchProvider {

	protected $ID = 'Pin';

	public function query($query, $_ = NULL)
	{
		$grid = (new \Pin\Widget\Grid);
		$result = new SearchresultCollection;

		$grid->setFilter([ 'description' => $query ]);
		$grid->setLimit(self::DefaultFeedLimit);

		$pins = $grid->getPins();

		foreach($pins as $pin)
		{
			$icon = 'icon-search ' . ($pin->product ? 'icon-search-item' : 'icon-search-pin');
			$label = $_ ? $_->toString($pin->product ? 'Items' : 'Pins') : NULL;
			//$price = $pin->product ? \Currency\Helper\Format::format($pin->price, \Store\Settings::getCurrencyCode($pin->user_id)) : NULL;
			//$price = $pin->product == 1 ? \Currency\Helper\Format::format_convert($pin->price, $pin->currency_code, \Currency\Helper\Format::setCode()) : NULL;
			if($pin->product && ($price = (new \Store\PinQuantity)->fetchRow([ 'pin_id = ?' => (int) $pin->id ], 'id ASC'))) {
//				$price =  \Currency\Helper\Format::format_convert($price->price, $pin->currency_code, \Currency\Helper\Format::setCode());
				$price =  $price->displayPrice();
			} else {
				$price = $pin->product == 1 ? \Currency\Helper\Format::format_convert($price->price, $pin->currency_code, \Currency\Helper\Format::setCode()) : NULL;
			}
			if($price)
				$price = ' - ' . $price;

			$result->add(new SearchResult('pin', $pin->id, String::cut($pin->title, 30) . $price, $pin->description, $this->url([ 'pin_id' => $pin->id, 'query' => $this->urlQuery($pin->title) ], 'pin'), \Pin\Helper\Image::getImage('small', $pin), $icon, $label, TRUE));
		}

		return $result;
	}

}