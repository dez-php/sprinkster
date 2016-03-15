<?php

namespace Source;

class IndexController extends \Base\PermissionController {
	
	public function init() { 
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() {	
		$request = $this->getRequest();
		
		//get category_id
		$this->source_id = $request->getRequest('source_id');
		
		$sourceTable = new \Source\Source();
		$source = $sourceTable->fetchRow($sourceTable->makeWhere(['id' => $this->source_id]));
		
		if(!$source) {
			$this->forward('error404');
		}
		
		//set page metatags
		$this->placeholder('meta_tags', $this->render('header_metas', ['source' => $source],null,true));
		
		//render script
		$this->render('index', ['source' => $source]);
		
	}
	
	
	
}

?>