<?php

namespace Color;

class Module extends \Core\Base\Module
{
	public function registerEvent( \Core\Base\Event $e, $application ) {
		$e->register('onBootstrap', [$this , 'onBootstrap']);
	}
	
	public function onBootstrap() {
		\Welcome\Module::setAllowed([ 'module' => [ 'color' ] ]);
	}
}
