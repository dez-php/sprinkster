<?php

namespace Youtubecrawler;

class Module extends \Core\Base\Module
{

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function isAccessible() {
    	return \Install\Modules::isInstalled(__NAMESPACE__);
    }
}
