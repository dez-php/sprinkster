<?php

namespace Newest\Widget;

class Homepage extends \Core\Base\Widget {

    protected $limit = 5;

	public function init() {
		$this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

    public function result() {
        if ((int) $this->limit < 1) {
            $this->limit = 5;
        }
        
        $this->render('index');
    }

}
