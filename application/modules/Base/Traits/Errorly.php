<?php

namespace Base\Traits;

trait Errorly
{

	public function errorly($key, $error_css_class = 'field-color-2')
	{
		if(!isset($this->errors[$key]))
			return;

		echo " $error_css_class";
	}
	
}