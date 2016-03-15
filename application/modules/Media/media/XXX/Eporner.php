<?php

return array('media_stubs' => array (
	'Eporner' => array(
		'title' => 'Eporner',
		'website' => 'http://www.eporner.com/',
		'url-match' => 'http://(?:www\.)?eporner\.com/([^\/]*)/([0-9]{1,12})',
		'embed-src' => 'http://www.eporner.com/player/$3',
		'embed-width' => '400',
		'embed-height' => '302',
// 		'iframe-player' => 'http://www.eporner.com/player/$3',
	)
));