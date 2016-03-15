<?php

namespace Bookmarklet\Widget;

class Manager extends \Base\Widget\PermissionWidget {

	public function result()
	{
		$pin = isset($this->options['pin']) ? $this->options['pin'] : NULL;
		$cover = $pin ? \Pin\Helper\Image::getImage('big', $pin, FALSE) : NULL;

		$this->render('index', [ 'pin' => $pin, 'cover' => $cover ]);
	}

}