<?php

namespace Search;

use \Base\Model\SearchProviderContainer;

class SearchController extends \Core\Base\Action {

	const MinSearchLength = 3;

	public function init()
	{
		$this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
		$this->noLayout(TRUE);
	}

	public function autocompleteAction()
	{
		$request = $this->getRequest();
		$query = $request->getPost('value');

		if(!$query || self::MinSearchLength > mb_strlen($query) || !$request->isXmlHttpRequest())
			return $this->responseJsonCallback(FALSE);

		$result = SearchProviderContainer::query($query, $this->_);
		
		return $this->responseJsonCallback($result->items());
	}

}