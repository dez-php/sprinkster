<?php

return array('media_stubs' => array (
	'Pornhub' => array(
		'title' => 'Pornhub',
		'website' => 'http://www.pornhub.com',
		'url-match' => 'http://(?:www\.)?pornhub\.com/view_video.php\?viewkey=([0-9]{1,12})',
		'embed-src' => 'http://www.pornhub.com/embed/$2',
		'embed-width' => '400',
		'embed-height' => '302',
		'iframe-player' => 'http://www.pornhub.com/embed/$2',
	)
));