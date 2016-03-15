<?php
return array(
	"session" => array(
//		"adapter" => "db",
		"options" => array(
			"save_handler" => "user",
			"namespace" => "pintastic",
			"params" => array(
				"cookie_lifetime" => 158112000,
				"use_cookies" => "On",
				"use_trans_sid" => "Off",
			),
		),
	)
);