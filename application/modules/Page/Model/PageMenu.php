<?php

namespace Page;

class PageMenu extends \Base\Model\Reference {
	
	protected $_referenceMap    = array(
			'Page' => array(
					'columns'           => 'page_id',
					'refTableClass'     => 'Page\Page',
					'referenceMap'		=> array(
							'columns'           => 'id',
							'refTableClass'     => 'Page\PageDescription',
							'refColumns'        => 'page_id',
							'where'				=> '"language_id = " . \Core\Base\Action::getModule(\'Language\')->getLanguageId()'
					),
					'refColumns'        => 'id'
			),
	);
	
	//virtual map for reference
	protected $_referenceReverseMap    = array(
			'Page\Page' => array(
					'columns'           => 'id',
					'refTableClass'     => 'Page\PageMenu',
					'refColumns'        => 'page_id',
					'singleRow'			=> true
			),
	);
	
	public function fetchMenu() {
		$sql = $this->getAdapter()->select()
					->from('page_menu','')
					->joinLeft('page', 'page_menu.page_id = page.id')
					->joinLeft('page_description', 'page.id = page_description.page_id', 'title')
					->where('page_description.language_id = ?', \Core\Base\Action::getModule('Language')->getLanguageId())
					->order(new \Core\Db\Expr('page_description.title ASC'));
		$results = $this->getAdapter()->fetchAll($sql, array(), \Core\Db\Init::FETCH_OBJ);	
		array_map(function($page) {
			\Page\Router\Regex::$cacheSelect[$page->id] = $page->key;
		}, $results);
		return $results;
	}
	
}