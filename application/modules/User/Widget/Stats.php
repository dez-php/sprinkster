<?php
namespace User\Widget;

use \DateTime;
use \Core\Db\Init;
use \Core\Db\Expr;
use \Currency\Helper\Format;
use \Pin\Pin;

class Stats extends \Core\Base\Widget
{
	private $action = NULL;

	public function init()
	{
		$this->me = \User\User::getUserData();
		$this->action = \Core\Base\Action::getInstance();


		if(!$this->me->id)
			$this->redirect($this->url([ 'controller' => 'login' ], 'user_c'));

		$this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function marketplaceAvailable()
	{
		return $this->action->isModuleAccessible('Seller') && $this->action->isModuleAccessible('Store');
	}

	public function accessible($module)
	{
		return $this->action->isModuleAccessible($module);
	}

	public function isSeller()
	{
		if(!$this->marketplaceAvailable())
			return;

		return \Seller\Helper\Subscription::isSeller($this->me->id);
	}

	public function result()
	{
		$userData = \User\User::getUserData();
		$this->render('stats', [ 'me' => $this->me ]);
	}

	public function statbox($title, $from, $to)
	{
		if(!$this->isSeller())
			return FALSE;

		if(!$title || !$from || !$to)
			return;

		if(!($from instanceof DateTime) || !($to instanceof DateTime))
			return;

		$this->render('statbox', [ 'me' => $this->me, 'title' => $this->_($title), 'from' => $from, 'to' => $to ]);
	}

	public function countOrders($from, $to, $status = NULL)
	{
		if(!$this->isSeller())
			return FALSE;

		if(!$from || !$to)
			return;

		if(!($from instanceof DateTime) || !($to instanceof DateTime))
			return;

		$db = Init::getDefaultAdapter();

		$sql = '
			SELECT COUNT(o.id)
			FROM `order` o
			WHERE
				o.seller_id = ?
			AND
				(o.status_id = ? OR ? IS NULL)
			AND
				o.created_at >= ?
			AND
				o.created_at <= ?
		';

		return (int) $db->fetchOne($sql, [
			(int) $this->me->id,
			(int) $status,
			$status,
			$from->format('Y-m-d 00:00:00'),
			$to->format('Y-m-d 23:59:59')
		]);
	}

	public function calc($expr, $from, $to, $status = NULL)
	{
		if(!$this->isSeller())
			return FALSE;

		if(!$expr || !$from || !$to)
			return FALSE;

		if(!($from instanceof DateTime) || !($to instanceof DateTime))
			return FALSE;

		$db = Init::getDefaultAdapter();
		$currency = \Store\Settings::getCurrencyCode($this->me->id);

		$sql = '
			SELECT
				' . new Expr('o.' . $expr) . ' AS value,
				o.currency
			FROM `order` o
			WHERE
				o.seller_id = ?
			AND
				(o.status_id = ? OR ? IS NULL)
			AND
				o.created_at >= ?
			AND
				o.created_at <= ?
		';

		$orders = $db->fetchAll($sql, [
			(int) $this->me->id,
			(int) $status,
			$status,
			$from->format('Y-m-d 00:00:00'),
			$to->format('Y-m-d 23:59:59')
		]);

		if(!$orders || empty($orders))
			return Format::format(0, $currency);

		$result = 0;

		foreach($orders as $order)
			$result += $order['currency'] === $currency ? (float) $order['value'] : (float) Format::convert((float) $order['value'], $order['currency'], $currency);

		return Format::format($result, $currency, 1);
	}

	public function calcPerCurrency($expr, $from, $to, $status = NULL)
	{
		if(!$this->isSeller())
			return FALSE;

		if(!$expr || !$from || !$to)
			return FALSE;

		if(!($from instanceof DateTime) || !($to instanceof DateTime))
			return;

		$db = Init::getDefaultAdapter();
		$currency = \Store\Settings::getCurrencyCode($this->me->id);

		$sql = '
			SELECT
				' . new Expr('o.' . $expr) . ' AS value,
				o.currency
			FROM `order` o
			WHERE
				o.seller_id = ?
			AND
				(o.status_id = ? OR ? IS NULL)
			AND
				o.created_at >= ?
			AND
				o.created_at <= ?
		';

		$orders = $db->fetchAll($sql, [
			(int) $this->me->id,
			(int) $status,
			$status,
			$from->format('Y-m-d 00:00:00'),
			$to->format('Y-m-d 23:59:59')
		]);

		if(!$orders || empty($orders))
			return [ $currency => Format::format(0, $currency) ];

		$result = [];

		foreach($orders as $order)
			$result[$order['currency']] = (isset($result[$order['currency']]) ? (float) $result[$order['currency']] : 0) + (float) $order['value'];

		if(empty($result))
			$result[$currency] = 0;

		foreach($result as $c => $value)
			$result[$c] = Format::format($value, $c, 1);

		return $result;
	}

	public function mostViewed($product = TRUE, $limit = 5)
	{
		$db = Init::getDefaultAdapter();

		$sql = '
			SELECT
				p.*,
				(SELECT SUM(v.views) FROM pin_view v WHERE v.pin_id = p.id) views,
				(SELECT COUNT(l.id) FROM pin_like l WHERE l.pin_id = p.id AND status = 1) likes,
				(SELECT COUNT(c.id) FROM pin_comment c WHERE c.pin_id = p.id AND status = 1) comments
			FROM pin p
			WHERE
				p.user_id = ?
			AND
				p.product = ?
			ORDER BY
				views DESC
			LIMIT
				?
		';

		$pins = $db->fetchAll($sql, [ (int) $this->me->id, $this->isSeller() ? (int) !!$product : 0, (int) $limit ]);

		if(!$pins)
			return NULL;

		$ids = [];
		$vws = [];
		$lks = [];
		$cmt = [];
		$pinsArray = [];
		foreach($pins as $p)
		{
			$ids[] = (int) $p['id'];
			$vws[] = (int) $p['views'];
			$lks[] = (int) $p['likes'];
			$cmt[] = (int) $p['comments'];
			$pinsArray[] = $p;
		}

		array_walk($ids, 'intval');

		if(empty($ids))
			$ids = [ 0 ];

		return [
			'pins' => (new \Pin\Pin())->pinToRowset($pinsArray),
			'views' => $vws,
			'likes' => $lks,
			'comments' => $cmt,
		];
	}

	public function bestSellers($limit = 5)
	{
		if(!$this->isSeller())
			return FALSE;

		$db = Init::getDefaultAdapter();

		$sql = '
			SELECT
				p.*,
				(SELECT SUM(v.views) FROM pin_view v WHERE v.pin_id = p.id) views,
				(SELECT COUNT(l.id) FROM pin_like l WHERE l.pin_id = p.id AND status = 1) likes,
				(SELECT COUNT(c.id) FROM pin_comment c WHERE c.pin_id = p.id AND status = 1) comments,
				IFNULL(
					(
						SELECT SUM(i.quantity)
						FROM store_item2purchase i
						INNER JOIN `order` o ON (i.purchase_id = o.id)
						WHERE
							i.pin_id = p.id
						AND
							o.status_id = ?
					),
					0
				) sold
			FROM pin p
			WHERE
				p.user_id = ?
			AND
				p.product = 1
			ORDER BY
				sold DESC
			LIMIT
				?
		';

		$pins = $db->fetchAll($sql, [ (int) \Base\Config::get('config_complete_status_id'), (int) $this->me->id, (int) $limit ]);

		if(!$pins)
			return NULL;

		$ids = [];
		$sld = [];
		$vws = [];
		$lks = [];
		$cmt = [];
		$pinsArray = [];
		
		foreach($pins as $p)
		{
			$ids[] = (int) $p['id'];
			$sld[] = (int) $p['sold'];
			$vws[] = (int) $p['views'];
			$lks[] = (int) $p['likes'];
			$cmt[] = (int) $p['comments'];
			$pinsArray[] = $p;
		}

		array_walk($ids, 'intval');

		if(empty($ids))
			$ids = [ 0 ];
		
		return [
			'pins' => (new \Pin\Pin())->pinToRowset($pinsArray),
			'sold' => $sld,
			'views' => $vws,
			'likes' => $lks,
			'comments' => $cmt,
		];
	}

	public function recentRatings($limit = 5)
	{
		if(!$this->marketplaceAvailable() || !$this->action->isModuleAccessible('Storerating'))
			return NULL;

		return (new \Storerating\StoreRating)->fetchAll([ 'rated_id = ?' => $this->me->id ], 'created_at DESC', (int) $limit);
	}

}