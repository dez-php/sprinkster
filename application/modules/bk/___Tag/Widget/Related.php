<?php

namespace Tag\Widget;

class Related extends \Base\Widget\PermissionWidget {

	protected $pin;

    public function init() {
        $this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
    }
	
	/**
	 * @param \Core\Db\Table\Row\AbstractRow $pin
	 * @return \Pin\Widget\Comment
	 */
	public function setPin($pin) {
		$this->pin = $pin;
		return $this;
	}
	
	/**
	 * @return \Core\Db\Table\Row\AbstractRow
	 */
	public function getPin() {
		return $this->pin;
	}

	public function result() { 
    	if($this->pin && isset($this->pin->pin_tags_related_total) && (int)$this->pin->pin_tags_related_total) {
    		$this->render('index', ['pin' => $this->pin]);
    	}
    }
    
//     public function getPinsAction() {
//     	$request = $this->getRequest();
//     	$pin_id = (int)$request->getPost('pin_id');
//     	$total = (int)$request->getPost('total');
//     	$json['html'] = null;
//     	if($pin_id > 0 && $total > 0) {
//     		$json['html'] = (string)$this->widget('pin.widget.grid', [
// 				'filter' => [ 'callback' => [ 'id' => '\Tag\PinTag::getRelated(' . $pin_id. ', ' . $total . ')']],
// 				'order' => [ 'callback' => [ 'pin.id' => '\Tag\PinTag::getRelated(' . $pin_id. ', ' . $total . ')']],
// 				'hide-filter' => TRUE,
// 				'event_class' => 'event-masonry-' . $this->getId() . ($request->isXmlHttpRequest() ? '-popup' : '')
//     		]);
//     	}
//     	$this->responseJsonCallback($json);
//     }

}
