<?php

return array('media_stubs' => array (
	'Keezmovies' => array(
		'title' => 'Keezmovies',
		'website' => 'http://www.keezmovies.com',
		'url-match' => 'https?://(?:www\.)?keezmovies\.com/(.*)/([^/]*)/?',
		'embed-src' => 'http://www.keezmovies.com/embed/$3',
		'embed-width' => '400',
		'embed-height' => '302',
		'iframe-player' => 'http://www.keezmovies.com/embed/$3',
	)
));