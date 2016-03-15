<?php

namespace Category;

class IndexController extends \Base\PermissionController {
	
	const Cols = 5;
	const Depth = 2;

	public function init()
	{
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction()
	{
		$request = $this->getRequest();
		
		//get category_id
		$this->category_id = $request->getRequest('category_id');
		
		//init category row table
		$categoryRowset = Category::get($this->category_id);
		
		if( !$categoryRowset )
			$this->forward('error404');
		
		if(isset($categoryRowset->module) && $categoryRowset->module && \Install\Modules::isInstalled($categoryRowset->module))
			$this->forward($categoryRowset->action, array('category' => $categoryRowset), $categoryRowset->controller, $categoryRowset->module);
		
		if($categoryRowset->parent_id)
			$categoryRowset->title = Category::getPathFromChild($categoryRowset->id);
		
		//set page metatags
		$this->placeholder('meta_tags', $this->render('header_metas', ['category' => $categoryRowset], NULL, TRUE));
		

		//render view
		$this->render('index', ['category' => $categoryRowset]);
	}

	public function allAction()
	{
		$request = $this->getRequest();

		$categories = \Category\Category::getTree(NULL, self::Depth);

		$containers = [];
		for($i = 0; $i < self::Cols; $i++)
			$containers[$i] = [];

		if($categories && 0 < count($categories))
		foreach($categories as $index => $category)
			$containers[$index % self::Cols][] = $category;

		$this->render('all', [ 'containers' => $containers ])
;	}

}