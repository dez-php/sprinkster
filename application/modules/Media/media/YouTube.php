<?php

return array('media_stubs' => array (
	'YouTube' => array (
		'title' => 'YouTube',
		'website' => 'http://www.youtube.com',
		'url-match' => 'https?://(?:video\.google\.(?:com|com\.au|co\.uk|de|es|fr|it|nl|pl|ca|cn)/(?:[^"]*?))?(?:(?:www|au|br|ca|es|fr|de|hk|ie|in|il|it|jp|kr|mx|nl|nz|pl|ru|tw|uk)\.)?youtube\.com(?:[^"]*?)?(?:&|&amp;|/|\?|;|\%3F|\%2F)(?:video_id=|v(?:/|=|\%3D|\%2F))([0-9a-z-_]{11})',
		'embed-src' => 'http://www.youtube.com/v/$2&rel=0&fs=1&hd=1',
		'embed-width' => '480',
		'embed-height' => '295',
		'image-src' => 'http://img.youtube.com/vi/$2/0.jpg',
		'iframe-player' => 'http://www.youtube.com/embed/$2?autohide=1&theme=light&hd=1&modestbranding=1&rel=0&showinfo=0&showsearch=0&wmode=transparent&autoplay=0' 
	)
));