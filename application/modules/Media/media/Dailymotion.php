<?php

return array('media_stubs' => array (
	'Dailymotion' => array (
		'title' => 'Dailymotion',
		'website' => 'http://www.dailymotion.com',
		'url-match' => 'http://(?:www\.)?dailymotion\.(?:com|alice\.it)/(?:(?:[^"]*?)?video|swf)/([a-z0-9]{1,18})',
		'embed-src' => 'http://www.dailymotion.com/swf/$2&related=0',
		'embed-width' => '420',
		'embed-height' => '339',
		'image-src' => 'http://www.dailymotion.com/thumbnail/160x120/video/$2' 
	)
));