<?php

namespace Pin;

class PinReportCategory extends \Base\Model\Reference {
	
	protected $_referenceMap    = array(
				'PinReportCategoryDescription' => array(
						'columns'           => 'id',
						'refTableClass'     => 'Pin\PinReportCategoryDescription',
						'refColumns'        => 'report_category_id',
						'where'				=> '"language_id = " . \Core\Base\Action::getModule(\'Language\')->getLanguageId()'
				),
	);
	
	public function getAll() {
		$db = \Core\Db\Init::getDefaultAdapter();
		$sql = $db->select()
					->from($this->_name)
					->joinLeft($this->_name . '_description', $this->_name.'.id='.$this->_name.'_description.report_category_id',array('title'))
					->where($this->_name . '_description.language_id = ?', \Core\Base\Action::getModule('Language')->getLanguageId())
					->order($this->_name . '_description.title');
					
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