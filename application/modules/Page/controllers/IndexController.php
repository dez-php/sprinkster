<?php

namespace Page;

class IndexController extends \Base\PermissionController {
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() {	
		$request = $this->getRequest();
		
		//get page_id
		$page_id = $request->getRequest('page_id');
		
		$pageTable = new \Page\Page();
		$page = $pageTable->get($page_id);
		
		if(!$page)
			$this->forward('error404');
		
		if(preg_match_all('~\$\{([^\}]*)\}~i', $page->description, $match, PREG_SET_ORDER)) {
			$front = $this->getFrontController();
			$search = $replace = array();
			foreach($match AS $m) {
				$search[] = '~'.preg_quote($m[0]).'~';
				$replace[] = $this->widget('page.widget.'.str_replace('_','',$m[1]));
			}
			$page->description = preg_replace($search, $replace, $page->description);
		}
		
		$data['page'] = $page;
		
		//set page metatags
		$this->placeholder('meta_tags', $this->render('header_metas', $data,null,true));
		
		//render view
		$this->render('index', $data);
		
	}
	
	
}