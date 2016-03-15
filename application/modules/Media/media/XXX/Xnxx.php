<?php

return array('media_stubs' => array (
	'Xnxx' => array(
		'title' => 'Xnxx',
		'website' => 'http://www.xnxx.com/',
		'url-match' => 'http://(?:www\.)?(?:video\.)?xnxx.com/video([0-9]{1,12})',
		'embed-src' => 'http://flashservice.xvideos.com/embedframe/$2',
		'embed-width' => '400',
		'embed-height' => '302',
		'iframe-player' => 'http://flashservice.xvideos.com/embedframe/$2',
	)
));