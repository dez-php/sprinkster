<?php

namespace Tag\Widget;

class Pinform extends \Base\Widget\PermissionWidget {
	
	protected $pin;
	
	public function init()
	{
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function result()
	{
		$data = [];

		if($this->pin instanceof \Pin\PinRow && isset($this->pin->pin_tags) && trim($this->pin->pin_tags))
			$data['tags'] = (new \Tag\Tag)->fetchAll([ 'id IN (?)' => array_map(function($id) { return (int)$id; }, explode(',',$this->pin->pin_tags)) ]);

		$this->render('index', $data);
	}
}