<?php

namespace Base\Traits;

trait WysiwygFilter
{

	public function filterWysiwyg($key)
	{
		$value = $this->getRequest()->getRequest('description');

		$value = html_entity_decode($value, ENT_QUOTES, 'utf-8');
		$value = strip_tags($value, '<b><strong><i><u><s><p><div><sub><sup><h4><h5><h6><a><ul><ol><li><br><br/><span>');
		$value = htmlspecialchars($value, ENT_QUOTES, 'utf-8');

		return $value;
	}
	
}