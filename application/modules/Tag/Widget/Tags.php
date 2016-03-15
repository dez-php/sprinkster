<?php

namespace Tag\Widget;

class Tags extends \Base\Widget\PermissionWidget {
	
	protected $pin;
	
	public function init()
	{
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function result()
	{
		if(!$this->pin || !isset($this->pin->pin_tags) || !trim($this->pin->pin_tags))
			return;
		
		$tags = (new \Tag\Tag)->fetchAll([ 'id IN (?)' => array_map(function($id) { return (int)$id; }, explode(',',$this->pin->pin_tags)) ]);
		$this->render('index', [ 'tags' => $tags, 'pin' => $this->pin ]);
	}
}