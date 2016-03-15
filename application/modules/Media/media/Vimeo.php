<?php

return array('media_stubs' => array (
	'Vimeo' => array (
		'title' => 'Vimeo',
		'website' => 'http://www.vimeo.com',
		'url-match' => 'https?://(?:www\.)?vimeo\.com/([0-9]{1,12})',
		'embed-src' => 'http://vimeo.com/moogaloop.swf?clip_id=$2&server=vimeo.com&fullscreen=1&show_title=1&show_byline=1&show_portrait=0&color=01AAEA',
		'embed-width' => '400',
		'embed-height' => '302',
		'iframe-player' => 'http://player.vimeo.com/video/$2' 
	)
));