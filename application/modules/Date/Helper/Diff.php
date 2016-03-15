<?php

namespace Date\Helper;

class Diff {
	
	protected $times;

	public function __construct($time1, $time2, $precision = 7, $full = false) {
		// If not numeric then convert texts to unix timestamps
		if (!is_int($time1)) {
			$time1 = \Core\Date::dateToUnix($time1);
		}
		if (!is_int($time2)) {
			$time2 = \Core\Date::dateToUnix($time2);
		}
		
		// If time1 is bigger than time2
		// Then swap time1 and time2
		if ($time1 > $time2) {
			$ttime = $time1;
			$time1 = $time2;
			$time2 = $ttime;
		}
		
		// Set up intervals and diffs arrays
		$intervals = array('year','month','week','day','hour','minute','second');
		$diffs = array();
		
		// Loop thru all intervals
		foreach ($intervals as $interval) {
			// Set default diff to 0
			$diffs[$interval] = 0;
			// Create temp time from time1 and interval
			$ttime = strtotime("+1 " . $interval, $time1);
			// Loop until temp time is smaller than time2
			while ($time2 >= $ttime) {
				$time1 = $ttime;
				$diffs[$interval]++;
				// Create new temp time from time1 and interval
				$ttime = strtotime("+1 " . $interval, $time1);
			}
		}
		
		$count = 0;
		$times = array();
		// Loop thru all diffs
		foreach ($diffs as $interval => $value) {
			// Break if we have needed precission
			if (!$full && $count >= $precision) {
				break;
			}
			// Add value and interval
			// if value is bigger than 0
			if ($value > 0 || $full) {
				// Add s if value is not 1
				if ($value != 1) {
					$interval .= "s";
				}
				// Add value and interval to times array
				$obj = new \stdClass();
				$obj->key = $interval;
				$obj->value = $value;
				$times[] = $obj;
				$count++;
			}
		}
		$this->times = $times;
	}
	
	public function getSingle() {
		$times = $this->times;
		return array_shift($times);
	}
	
	public function getAll() {
		return $this->times;
	}
	
}