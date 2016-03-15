<?php

namespace Pin;

class PinCommentReportCategory extends \Base\Model\Reference {
	
	protected $_referenceMap    = array(
				'PinCommentReportCategoryDescription' => array(
						'columns'           => 'id',
						'refTableClass'     => 'Pin\PinCommentReportCategoryDescription',
						'refColumns'        => 'comment_report_category_id',
						'where'				=> '"language_id = " . \Core\Base\Action::getModule(\'Language\')->getLanguageId()'
				),
	);
	
	public function getAll() {
		$db = \Core\Db\Init::getDefaultAdapter();
		$sql = $db->select()
					->from($this->_name)
					->joinLeft($this->_name . '_description', $this->_name.'.id='.$this->_name.'_description.comment_report_category_id',array('title'))
					->where($this->_name . '_description.language_id = ?', \Core\Base\Action::getModule('Language')->getLanguageId())
					->order('sort_order ASC');
					
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