<?php

return array('media_stubs' => array (
	'Extremetube' => array(
		'title' => 'Extremetube',
		'website' => 'http://www.extremetube.com',
		'url-match' => 'https?://(?:www\.)?extremetube\.com/video/(.*)/?',
		'embed-src' => 'http://www.extremetube.com/embed/$2',
		'embed-width' => '400',
		'embed-height' => '302',
		'iframe-player' => 'http://www.extremetube.com/embed/$2',
	)
));