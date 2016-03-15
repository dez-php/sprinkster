<?php

namespace Base\Traits;

trait FormInputPopulator
{

	public function input($key, $default = NULL)
	{
		return ($this->getRequest()->issetPost($key)) ? $this->getRequest()->getPost($key) : $default;
	}

	public function radio($key, $current, $default = FALSE)
	{
		if($this->getRequest()->isPost() && $this->getRequest()->isSetPost($key))
			return $current == $this->getRequest()->getPost($key) ? ' checked="checked"' : '';

		return $current == $default ? ' checked="checked"' : '';
	}
	
	public function checked($key, $default = FALSE)
	{
		if($this->getRequest()->isPost())
			return $this->getRequest()->issetPost($key) ? ' checked="checked"' : '';

		return $default ? ' checked="checked"' : '';
	}

	public function selected($key, $current, $default = NULL)
	{
		if($this->getRequest()->isPost())
			return $current == $this->getRequest()->getPost($key) ? ' selected="selected"' : '';

		return $current == $default ? ' selected="selected"' : '';
	}
	
}