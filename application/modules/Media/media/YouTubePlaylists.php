<?php

return array('media_stubs' => array (
	'YouTubePlaylists' => array (
		'title' => 'YouTube (Playlists)',
		'website' => 'http://www.youtube.com',
		'url-match' => 'https?://(?:(?:www|au|br|ca|es|fr|de|hk|ie|in|il|it|jp|kr|mx|nl|nz|pl|ru|tw|uk)\.)?youtube\.com(?:[^"]*?)?(?:&|&amp;|/|\?|;)(?:id=|p=|p/)([0-9a-f]{16})',
		'embed-src' => 'http://www.youtube.com/p/$2&rel=0&fs=1',
		'embed-width' => '480',
		'embed-height' => '385' 
	)
));