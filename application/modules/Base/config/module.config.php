<?php

//base config
$config = array(
		
	'subscribe_intervals' => [
		'day' => 'Day', 'week' => 'Week', 'month' => 'Month', 'year' => 'Year'
	],
		
	//pin images
	'pinThumbs' => array(
		'small' 	=> '104x104-c',
		'medium' 	=> '280x0',
		'big' 		=> '800x0',
	),
	
	// pin thumb gallery sizes
	'pinGallery' => array(
		'small' 	=> '100x100-c',
		'medium' 	=> '270x270-c',
		'big' 		=> '800x0'
	),
		
	'userAvatars' => array(
		'small' 	=> '60x60-c',
		'medium' 	=> '200x200-c',
	),
	'userCovers' => array(
		'small'	=>'1150x0'
	),
		
	'wishlistCovers' => array(
		'small'	=>'1600x280-c'
	)
);

return $config;