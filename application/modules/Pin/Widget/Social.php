<?php

namespace Pin\Widget;

class Social extends \Base\Widget\PermissionWidget {

    public function init() {
        $this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
    }

    public function result() {
        $this->render('index');
    }

}
