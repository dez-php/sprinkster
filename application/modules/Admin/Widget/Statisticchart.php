<?php

namespace Admin\Widget;

class Statisticchart extends \Base\Widget\AbstractMenu {
	
	protected $parent_id = null;
	
	/* (non-PHPdoc)
	 * @see \Core\Base\Action::init()
	 */
	public function init() {
		$this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	/* (non-PHPdoc)
	 * @see \Core\Base\Widget::result()
	 */
	public function result() {
		
		$this->render('index');
	}
	
	public function chartAction() {
		if(preg_match('/^20([0-9]{2})$/', $this->getRequest()->getQuery('year'))) {
			$year = $this->getRequest()->getQuery('year');
		} else {
			$year = \Core\Date::getInstance(null, 'yy', true)->toString();
		}
		 
		$data['xAxis'] = array('categories' => array());
		$data['series'] = array(
				array('name' => $this->_('Pins'), 'data' => array()),
				array('name' => $this->_('Users'), 'data' => array()),
				array('name' => $this->_('Wishlists'), 'data' => array()),
				array('name' => $this->_('Waiting for invitation'), 'data' => array()),
				array('name' => $this->_('Orders'), 'data' => array())
		);
		 
		$get_m = array();
		
		$statisticTable = new \Core\Db\Table('statistics');
		$results = $statisticTable->fetchAll("`id` LIKE '".$year."%'");
		
		if($results) {
			foreach($results AS $t) {
				$get_m[$t['id']][$t['type']] = $t['total'];
			}
		}
		 
		for( $i = 1; $i < 13; $i++ ) {
			if($year == date('Y') && $i > date('m')) {
				continue;
			}
		
			$data['xAxis']['categories'][] = \Core\Date::getInstance(date('Y').'-'.sprintf('%02d',$i).'-01', 'MM',true)->toString();
		
			for($r = 1; $r <= count($data['series']); $r++ ) {
				if( isset($get_m[$year . sprintf('%02d',$i)][$r]) ) {
					$total = (int)$get_m[$year . sprintf('%02d',$i)][$r];
				} else {
					$total = 0;
				}
				$data['series'][($r-1)]['data'][] = $total;
			}
		}
		 
		$this->responseJsonCallback($data);
	}
	
}