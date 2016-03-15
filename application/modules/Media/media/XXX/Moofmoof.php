<?php

return array('media_stubs' => array (
	'Moofmoof' => array(
		'title' => 'Moofmoof',
		'website' => 'http://www.moofmoof.com',
		'url-match' => 'http://(?:www\.)?moofmoof\.com/([0-9]{1,12})',
		'fetch-match' => '<param name="flashvars" value="id_video=([0-9]{1,})"',
		'embed-src' => 'http://flashservice.xvideos.com/embedframe/$2',
		'embed-width' => '400',
		'embed-height' => '302',
		'iframe-player' => 'http://flashservice.xvideos.com/embedframe/$2',
	)
));