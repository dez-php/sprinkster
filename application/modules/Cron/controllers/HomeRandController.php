<?php

namespace Cron;

class HomeRandController extends \Core\Base\Action {

	public function init() {
		$this->noLayout(true);
		set_time_limit(0);
		ignore_user_abort(true);
	}
	
	public function indexAction() {
		$pinTable = new \Pin\Pin();
		$query = $pinTable->select()
			->from($pinTable, array('max' => 'MAX(id)', 'min' => 'MIN(id)', 'total' => 'COUNT(id)'))
			->where('status = 1')
			->limit(1);
		
		$max_min  = $pinTable->fetchRow($query);
		if($max_min && $max_min->total) {
			$pp = \Base\Config::get('pins_per_page');
			$loop = 50*$pp;
			if($max_min->total <= $loop*4) {
				$pins = $pinTable->getAdapter()->fetchPairs($pinTable->select()->from($pinTable,array('id','id'))->where('status = 1')->order('RAND()'));
			} else {
				$pins = $pins_array = array();
				while ( COUNT($pins) < $loop ) {
					$pin_id = mt_rand($max_min->min, $max_min->max);
					if(isset($pins_array[$pin_id])) {
						continue;
					}
					if(count($pins_array) == $max_min->total) {
						break;
					}
					if($pinTable->countById_Status($pin_id,1)) {
						$pins[$pin_id] = $pin_id;
					}
					$pins_array[$pin_id] = $pin_id;
				}
			}
			\Base\Model\Cache::set('home.pins',$pins);
		}
	}
	
}