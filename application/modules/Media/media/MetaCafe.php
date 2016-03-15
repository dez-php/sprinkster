<?php

return array('media_stubs' => array (
	'MetaCafe' => array (
		'title' => 'MetaCafe',
		'website' => 'http://www.metacafe.com',
		'url-match' => 'http://(?:www\.)?metacafe\.com/(?:watch|fplayer)/(\w{1,10})/',
		'embed-src' => 'http://www.metacafe.com/fplayer/$2/metacafe.swf',
		'embed-width' => '400',
		'embed-height' => '345' 
	)
));