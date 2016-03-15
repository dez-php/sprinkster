<?php

namespace Pin\Widget\Action;

use \Core\Interfaces\ICacheableWidget;

class Edit extends \Core\Base\Widget implements ICacheableWidget {

    protected $pin;
    protected $template;

    public function init() {
        $this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
    }

    public function result() {
        if ($this->pin && $this->pin->id) {
            $this->render($this->template, array('pin' => $this->pin));
        }
    }

}
