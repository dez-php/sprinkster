<?php

return array('media_stubs' => array (
	'Cliphunter' => array(
		'title' => 'Cliphunter',
		'website' => 'http://www.cliphunter.com/',
		'url-match' => 'http://(?:www\.)?cliphunter\.com/w/([0-9]{1,12})',
		'embed-src' => 'http://www.cliphunter.com/embed/$2',
		'embed-width' => '400',
		'embed-height' => '302',
		'iframe-player' => 'http://www.cliphunter.com/embed/$2',
	)
));