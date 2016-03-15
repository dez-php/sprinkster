<?php

namespace Cron;

use \Paymentgateway\Payment;

use \Paymentgateway\ItemCollection;
use \Paymentgateway\OrderManager;

class RandumController extends \Core\Base\Action {
	
	private $methodsArray = [
		'follow' => '10',
		'likePin' => '10',
		'repinPin' => '10',
		'addPoweruser' => '20',
		'addVipPin' => '20',
		'addGeolocation' => '20',
		'likeInterest' => '20',
	];
	
	private $totalPins = 0, 
			$totalUser = 0;
	
	public function init() {
		$this->noLayout(true);
		set_time_limit(0);
		ignore_user_abort(true);
	}
	
	public function indexAction() {
		
		$this->totalPins = (new \Pin\Pin())->countByStatus(1);
		$this->totalUser = (new \User\User())->countByStatus(1);
		
		foreach($this->methodsArray AS $method => $limit) {
			$users = (new \User\User())->fetchAll(['status = 1 AND activity_open = date_added'], 'RAND()', $limit);
			foreach($users AS $user) {
				call_user_func([$this, $method], $user->id);
			}
		}
		
	}
	
	////////////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////////////
	
	// follow user
	private function follow($user_id) {
		
		if(!$this->totalUser)
			return;
		
		if( ceil($this->totalUser/10) <= (new \User\UserFollow())->countBy() )
			return;
		
		$related = $this->getRandUser($user_id);
		if(!$related)
			return;
		$followHelper = new \User\Helper\Follow($related->id, $user_id);
		if(!$followHelper->is_follow) {
			$followHelper->followUser();
		}
	}
	
	// like pin
	private function likePin($user_id) {
		
		if(!$this->totalPins)
			return;
		
		if( ceil($this->totalPins/6) <= (new \Pin\PinLike())->countBy() )
			return;
		
		$related = $this->getRandPin($user_id);
		if(!$related)
			return;
		$pinLikeTable = new \Pin\PinLike();
		$row = $pinLikeTable->fetchRow($pinLikeTable->makeWhere(array('user_id'=>$user_id,'pin_id'=>$related->id)));
		if(!$row) {
			$row = $pinLikeTable->fetchNew();
			$row->pin_id = $related->id;
			$row->user_id = $user_id;
			$row->date_added = \Core\Date::getInstance(null,\Core\Date::SQL_FULL,true)->toString();
			$row->save();
		}
	}
	
	// like interest
	private function likeInterest($user_id) {
		if(!$this->isModuleAccessible('Interest'))
			return;
		
		if( ceil($this->totalUser/10) <= (new \Interest\InterestFollow())->countBy() )
			return;
		
		$randum = (new \Interest\Interest())->fetchRow(null, 'RAND()');
		if(!$randum)
			return;
		
		$followTable = new \Interest\InterestFollow();
		if( $followTable->countByUserId_InterestId($user_id, $randum->id) )
			return;
		
		$new = $followTable->fetchNew();
		$new->user_id = $user_id;
		$new->interest_id = $randum->id;
		$new->save();
	}
	
	//pin repin
	private function repinPin($user_id) {
		$wishlistTable = new \Wishlist\Wishlist();
		
		if(!$this->totalPins)
			return;
		
		if( ceil($this->totalPins/15) <= (new \Pin\PinRepin())->countBy() )
			return;
		
		if($wishlistTable->countByUserId($user_id) > mt_rand(10,20))
			return;
		
		$pinRepinTable = new \Pin\PinRepin();
		
		if($pinRepinTable->countByUserId($user_id) > mt_rand(1000,2000))
			return;
			
		$wishlistTable->getAdapter()->beginTransaction();
		try {
			$related = $this->getRandPin($user_id);
			if(!$related)
				return;
			
			$wishlist = null;
			if(isset($related->pin_tags_related_total) && $related->pin_tags_related_total) {
				$tags = explode(',', $related->pin_tags);
				$wishlist = array_shift($tags);
				unset($tags);
			} else {
				$wordsAll = new \Core\Text\ParseWords( strip_tags(html_entity_decode($related->title,ENT_QUOTES,'utf-8')) );
				$words = $wordsAll->getMinLenght(4);
				if($words && $words->count()) {
					$words = (array)$words;
					$wishlist = array_shift($words);
					unset($words);
				}
			}
			
			if(!$wishlist)
				return;
			
			$row = $wishlistTable->fetchRow(['user_id = ?' => $user_id, 'title LIKE ?' => $wishlist]);
			if(!$row) {
				$row = $wishlistTable->fetchNew();
				$row->title = $this->escape($wishlist);
				$row->user_id = $user_id;
				$row->description = '';
				$row->email_me = 1;
				$row->save();
			}
			
			if($row->id) {
				$pins = $this->widget('pin.widget.grid', [
					'description' => $wishlist,
					'limit' => mt_rand(7,15)
				])->getPins()->toArray();
				$pins = array_map(function($pin) {
					return $pin['id'];
				}, $pins);
				$pins[] = $related->id;
				foreach($pins AS $pin) {
					if($pinRepinTable->countByUserId_PinId($user_id, $pin))
						continue;
					
					$new = $pinRepinTable->fetchNew();
					$new->wishlist_id = $row->id;
					$new->user_id = $user_id;
					$new->pin_id = $pin;
					$new->date_added = date('Y-m-d H:i:s');
					$new->save();
					
				}
			}
			
			$wishlistTable->getAdapter()->commit();
		} catch (\Exception $e) {
			$wishlistTable->getAdapter()->rollBack();
		}
	}
	
	//add vip pin
	private function addVipPin($user_id) {
		if(!$this->isModuleAccessible('Vip'))
			return;
		
		if(!$this->totalPins)
			return;
		
		$vipTable = new \Vip\VipPin();
		
		$pin = (new \Pin\Pin())->fetchRow(['user_id = ' . (int)$user_id . ' AND status = 1'], 'RAND()');
		if(!$pin || $vipTable->isVipPin($pin->id))
			return;
		
		if($vipTable->countByDueDate('>=' . \Core\Date::getInstance(null,\Core\Date::SQL_FULL, true)->toString()) > 10)
			return;
		
		$pacTable = new \Vip\VipPinPackage();
		$pack = $pacTable->fetchRow(null, 'RAND()');
		if(!$pack)
			return;
		$desc = $pack->findDependentRow('Vip\VipPinPackageDescription');
		
		$result = new ItemCollection;
		$result->add(new \Vip\SubscribeItem((object)[
				'id' => $pack->id,
				'title' => 'Featured Pin Demo',
				'description' => 'Featured Pin Demo',
				'price' => 0,
				'period' => 'day',
				'length' => mt_rand(1, 2),
				'pin_id' => $pin->id,
				'parent' => NULL
		]));
			
		$order = OrderManager::create(
				$user_id,
				NULL,
				'Vip',
				NULL,
				Payment::SUBSCRIPTION,
				$result,
				0,
				0,
				\Base\Config::get ( 'config_currency' ),
				TRUE,
				NULL,
				1
		);
	
		$order->provider_code = 'FREE';
		$order->setStatus(\Base\Config::get('config_complete_status_id'));
		$order->snapshot($this->_('Completed Free Order.'));

		$order->getRoute()->completeAction([ 'action' => 'pay' ]);
			
		
	}
	
	//add poweruser
	private function addPoweruser($user_id) {
		if(!$this->isModuleAccessible('Poweruser'))
			return;
		
		$powerUserTable = new \Poweruser\UserPower();
		
		if($powerUserTable->isPowerUser($user_id))
			return;
		
		if($powerUserTable->countByDueDate('>=' . \Core\Date::getInstance(null,\Core\Date::SQL_FULL, true)->toString()) > 30)
			return;
		
		$pacTable = new \Poweruser\UserPowerPackage();
		$pack = $pacTable->fetchRow(null, 'RAND()');
		if(!$pack)
			return;
		$desc = $pack->findDependentRow('Poweruser\UserPowerPackageDescription');
		
		$result = new ItemCollection;
		$result->add(new \Poweruser\SubscribeItem((object)[
			'id' => $pack->id,
			'title' => 'Power User Demo',
			'description' => 'Power User Demo',
			'price' => 0,
			'period' => 'day',
			'length' => mt_rand(1, 2),
			'parent' => null
		]));
			
		try {
			$order = OrderManager::create(
					$user_id,
					NULL,
					'Poweruser',
					NULL,
					Payment::SUBSCRIPTION,
					$result,
					0,
					0,
					\Base\Config::get ( 'config_currency' ),
					TRUE,
					NULL,
					1
			);
		
			$order->provider_code = 'FREE';
			$order->setStatus(\Base\Config::get('config_complete_status_id'));
			$order->snapshot($this->_('Completed Free Order.'));
				
			$order->getRoute()->completeAction([ 'action' => 'pay' ]);
		
		} catch (\Exception $e) {}
		
	}
	
	private function addGeolocation($user_id) {
		if(!$this->isModuleAccessible('Geolocation'))
			return;
		
		$pin = $this->getRandPinByUser($user_id);
		if(!$pin)
			return;
		
		$geoTable = new \Geolocation\Geolocation();
		
		if($geoTable->countByPinId($pin->id))
			return;
		
		$pinTable = new \Pin\Pin();
		
		if(!$this->totalPins)
			return;
		
		$totalGeo = $geoTable->countBy();
		$percent = (($totalGeo/$this->totalPins)*100);
		if($percent >= 30)
			return;
		
		$wordsAll = new \Core\Text\ParseWords( strip_tags(html_entity_decode($pin->title,ENT_QUOTES,'utf-8')) );
		$words = $wordsAll->getMinLenght(4);
		if(!$words || !$words->count())
			return;
		
		$place = null;
		foreach($words AS $word) {
			$result = @file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($word) . '&sensor=false');
			if($result && is_array($data = @json_decode($result,true))) {
				if(isset($data['status']) && $data['status'] == 'OK' && isset($data['results']) && count($data['results'])) {
					$rand = array_rand($data['results'], 1);
					$place = $data['results'][$rand];
					break;
				}
			}
		}
		
		if(!$place)
			return;
		
		$new = $geoTable->fetchNew();
		$format = $this->_formatPlace($place);
		
		$country_id = null;
		if($format['country']['short']) {
			$country = (new \Country\Country())->fetchRow(['iso_code_2 = ?' => $format['country']['short']]);
			if($country)
				$country_id = $country->id;
		}
		
		$new->setFromArray([
			'pin_id' => $pin->id,
			'lat' => $place['geometry']['location']['lat'],
			'lng' => $place['geometry']['location']['lng'],
			'address' => $this->escape($place['formatted_address']),
			'country' => $format['country']['long'],
			'country_code' => $format['country']['short'],
			'country_id' => $country_id,
			'state' => $format['administrative_area_level_1']['long'],
			'city' => $format['locality']['long'],
			'post_code' => $format['postal_code']['long'],
			'street' => $format['route']['long'],
			'street_number' => $format['street_number']['long']
		]);
		
		$new->save();
	}
	
	//help fnc
	private function getRandUser($user_id) {
		return (new \User\User())->fetchRow(['id != ' . (int)$user_id . ' AND status = 1'], 'RAND()');
	}
	
	private function getRandPin($user_id) {
		$pinTable = new \Pin\Pin();
		$pin = $pinTable->fetchRow(['user_id != ' . (int)$user_id . ' AND status = 1'], 'RAND()');
		return $pin ? $pinTable->get($pin->id) : null;
	}
	
	private function getRandPinByUser($user_id) {
		$pinTable = new \Pin\Pin();
		$pin = $pinTable->fetchRow(['user_id = ' . (int)$user_id . ' AND status = 1'], 'RAND()');
		return $pin ? $pinTable->get($pin->id) : null;
	}
	
	private function _formatPlace($place) {
		$componentForm = [
		  'street_number' => 'short_name',
		  'route' => 'long_name',
		  'locality' => 'long_name',
		  'administrative_area_level_1' => 'short_name',
		  'country' => 'long_name',
		  'postal_code' => 'short_name'
		];
		
		$return = [];
		foreach($componentForm AS $a => $b) {
			$return[$a] = [
				'short' => NULL,
				'long' => NULL
			];
		}
		
		foreach($place['address_components'] AS $comp) {
			foreach($comp['types'] AS $type) {
				if (isset($componentForm[$type]) && isset($comp[$componentForm[$type]])) {
					$return[$type] = [
						'short' => $comp['short_name'],
						'long' => $comp['long_name']
					];
					break;
				}
			}
		}
		return $return;
	}
	
}