<?php

return array('media_stubs' => array (
	'Xhamster' => array(
		'title' => 'Xhamster',
		'website' => 'http://xhamster.com',
		'url-match' => 'http://(?:www\.)?xhamster\.com/movies/([0-9]{1,12})',
		'embed-src' => 'http://xhamster.com/xembed.php?video=$2',
		'embed-width' => '400',
		'embed-height' => '302',
		'iframe-player' => 'http://xhamster.com/xembed.php?video=$2',
		'image-src' => 'http://et31.xhcdn.com/t/{substr($2,-3)}/3_b_$2.jpg',
	)
));