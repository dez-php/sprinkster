<?php

return array('media_stubs' => array (
	'Xtube' => array(
		'title' => 'Xtube',
		'website' => 'http://www.xtube.com/',
		'url-match' => 'http://(?:www\.)?xtube\.com/watch.php\?v=([^\&\#]*)',
		'embed-src' => 'http://cdn1.static.xtube.com/swf/videoPlayer_embed.swf?video_id=$2&clip_id=$2',
		'embed-width' => '400',
		'embed-height' => '302'
	)
));