<?php

return array('media_stubs' => array (
	'Redtube' => array(
		'title' => 'Redtube',
		'website' => 'http://www.redtube.com',
		'url-match' => 'http://(?:www\.)?redtube\.com/([0-9]{1,12})',
		'embed-src' => 'http://embed.redtube.com/player/?id=$2&style=redtube',
		'embed-width' => '400',
		'embed-height' => '302',
// 		'iframe-player' => 'http://embed.redtube.com/player/?id=$2&style=redtube',
		'flashvars' => 'id=217660&style=redtube&autostart=false'
	)
));