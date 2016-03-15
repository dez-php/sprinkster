<?php

return array('media_stubs' => array (
	'Vidz' => array(
		'title' => 'Vidz',
		'website' => 'http://www.vidz.com',
		'url-match' => 'http://(?:www\.)?vidz\.com/video/(.*)s=([0-9]{1,12})',
		'fetch-match' => '<param name="flashvars" value="id_scene=([0-9]{1,12})',
		'embed-src' => 'http://webdata.vidz.com/demo/swf/FlashPlayerV2.swf',
		'embed-width' => '400',
		'embed-height' => '302',
		'flashvars' => 'id_scene=$2&id_niche=-1&type=free'
// 		'iframe-player' => 'http://www.pornhub.com/embed/$2',
	)
));