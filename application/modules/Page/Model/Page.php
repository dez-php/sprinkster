<?php

namespace Page;

class Page extends \Base\Model\Reference {
	
	public function __construct($config = array()) {
		parent::__construct($config);
		$this->setRowClass('\Page\PageRow');
	}
	
	protected $_referenceMap    = array(
				'PageDescription' => array(
						'columns'           => 'id',
						'refTableClass'     => 'Page\PageDescription',
						'refColumns'        => 'page_id',
						'where'				=> '"language_id = " . \Core\Base\Action::getModule(\'Language\')->getLanguageId()'
				),
	);
	
	public function get($page_id) {
		$db = $this->getAdapter();
		$sql = $db->select()
					->from('page')
					->joinLeft('page_description', 'page.id = page_description.page_id',['*','page.id AS id'])
					->where($this->makeWhere(['id' => $page_id]))
					->where('language_id = ?', \Core\Base\Action::getModule('Language')->getLanguageId())
					->where('status = 1');

				
		$rows = $db->fetchRow($sql);

        if (!$rows) {
            return null;
        }

        $data = array(
            'table'   => $this,
            'data'     => $rows,
            'readOnly' => true,
            'stored'  => true
        );
		
        
        $rowClass = $this->getRowClass();
        if (!class_exists($rowClass)) {
            require_once 'Loader.php';
            \Core\Loader\Loader::loadClass($rowClass);
        }
        return new $rowClass($data);
	}
	
	public function getDescriptionByKey($key) {
		$page = $this->getByKey($key);
		if(!$page)
			return null;
		return $page->description;
	}
	
	public function getByKey($key) {
		$db = $this->getAdapter();
		$sql = $db->select()
					->from('page')
					->joinLeft('page_description', 'page.id = page_description.page_id',['*','page.id AS id'])
					->where($this->makeWhere(['key' => $key]))
					->where('language_id = ?', \Core\Base\Action::getModule('Language')->getLanguageId())
					->where('status = 1');

				
		$rows = $db->fetchRow($sql);

        if (!$rows) {
            return null;
        }

        $data = array(
            'table'   => $this,
            'data'     => $rows,
            'readOnly' => true,
            'stored'  => true
        );
		
        
        $rowClass = $this->getRowClass();
        if (!class_exists($rowClass)) {
            require_once 'Loader.php';
            \Core\Loader\Loader::loadClass($rowClass);
        }
        return new $rowClass($data);
	}
	
	public function getAll($where = null) {
		$db = $this->getAdapter();
		$sql = $db->select()
				->from('page')
				->joinLeft('page_description', 'page.id = page_description.page_id',['*','page.id AS id'])
				->where('language_id = ?', \Core\Base\Action::getModule('Language')->getLanguageId())
				->where('status = 1');
		
		if($where)
			$sql->where($where);
		
		$rows = $db->fetchAll($sql);
		
		$data  = array(
            'table'    => $this,
            'data'     => $rows,
            'readOnly' => true,
            'rowClass' => $this->getRowClass(),
            'stored'   => true
        );

        $rowsetClass = $this->getRowsetClass();
        if (!class_exists($rowsetClass)) {
            require_once 'Loader.php';
            \Core\Loader\Loader::loadClass($rowsetClass);
        }

        return new $rowsetClass($data);
	}
}