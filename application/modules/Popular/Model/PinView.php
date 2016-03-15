<?php

namespace Popular;

class PinView extends \Pin\PinView {

	public static function Today() {
		$db = \Core\Db\Init::getDefaultAdapter();
		$now = \Core\Date::getInstance(null, \Core\Date::SQL_FULL, true)->toString();
		
		return $db->select()
				->from(['pin_view'=>$db->select()
							->from('pin_view','pin_id')
							->where('DATE(date_added) = DATE(?)', $now)
							->useIndex('date_added')
							->limit(5000)
							->group('pin_id')
							->order('COUNT(id) DESC')
							->order('date_added DESC')], 'pin_id');
					
	}
	
	public static function Week() {
		$db = \Core\Db\Init::getDefaultAdapter();
		$now = \Core\Date::getInstance(null, \Core\Date::SQL_FULL, true)->toString();
		$week = self::week_start_end_by_date( $now );
		
		return $db->select()
				->from(['pin_view'=>$db->select()
					->from('pin_view','pin_id')
					->where('date_added >= ?', $week['first_day_of_week'] . ' 00:00:00')
					->where('date_added <= ?', $week['last_day_of_week'] . ' 23:59:59')
					->useIndex('date_added')
					->limit(5000)
					->group('pin_id')
					->order('COUNT(id) DESC')
					->order('date_added DESC')], 'pin_id');
	}
	
	public static function Month() {
		$db = \Core\Db\Init::getDefaultAdapter();
		
		return $db->select()
			->from(['pin_view'=>$db->select()
				->from('pin_view','pin_id')
				->where('date_added >= ?', \Core\Date::getInstance(null, 'yy-mm-01 00:00:00', true)->toString())
				->where('date_added <= ?', \Core\Date::getInstance(null, 'yy-mm-t 23:59:59', true)->toString())
				->useIndex('date_added')
				->limit(5000)
				->group('pin_id')
				->order('COUNT(id) DESC')
				->order('date_added DESC')], 'pin_id');
	}
	
	public static function PrevWeek() {
		$db = \Core\Db\Init::getDefaultAdapter();
		$now = \Core\Date::getInstance(null, \Core\Date::SQL_FULL, true)->setInterval('-1 week')->toString();
		$week = self::week_start_end_by_date( $now );
		
		return $db->select()
		->from(['pin_view'=>$db->select()
				->from('pin_view','pin_id')
				->where('date_added >= ?', $week['first_day_of_week'] . ' 00:00:00')
				->where('date_added <= ?', $week['last_day_of_week'] . ' 23:59:59')
				->useIndex('date_added')
				->limit(5000)
				->group('pin_id')
				->order('COUNT(id) DESC')
				->order('date_added DESC')], 'pin_id');
	}
	
	public static function PrevMonth() {
		$db = \Core\Db\Init::getDefaultAdapter();
		
		return $db->select()
		->from(['pin_view'=>$db->select()
				->from('pin_view','pin_id')
				->where('date_added >= ?', \Core\Date::getInstance(null, 'yy-mm-01 00:00:00', true)->setInterval('-1 month')->toString())
				->where('date_added <= ?', \Core\Date::getInstance(null, 'yy-mm-t 23:59:59', true)->setInterval('-1 month')->toString())
				->useIndex('date_added')
				->limit(5000)
				->group('pin_id')
				->order('COUNT(id) DESC')
				->order('date_added DESC')], 'pin_id');
	}
	
	public static function week_start_end_by_date($date, $format = 'Y-m-d') {
	
		//Is $date timestamp or date?
		if (is_numeric($date) AND strlen($date) == 10) {
			$time = $date;
		}else{
			$time = strtotime($date);
		}
	
		$week['week'] = date('W', $time);
		$week['year'] = date('o', $time);
		$week['year_week']           = date('oW', $time);
		$first_day_of_week_timestamp = strtotime($week['year']."W".str_pad($week['week'],2,"0",STR_PAD_LEFT));
		$week['first_day_of_week']   = date($format, $first_day_of_week_timestamp);
		$week['first_day_of_week_timestamp'] = $first_day_of_week_timestamp;
		$last_day_of_week_timestamp = strtotime($week['first_day_of_week']. " +6 days");
		$week['last_day_of_week']   = date($format, $last_day_of_week_timestamp);
		$week['last_day_of_week_timestamp']  = $last_day_of_week_timestamp;
	
		return $week;
	}
	
}