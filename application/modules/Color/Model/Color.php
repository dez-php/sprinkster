<?php

namespace Color;

class Color extends \Base\Model\Reference {
	
	protected $_referenceMap    = array(
		'ColorDescription' => array(
			'columns'           => 'id',
			'refTableClass'     => 'Color\ColorDescription',
			'refColumns'        => 'color_id',
			'where'				=> '"language_id = " . \Core\Base\Action::getModule(\'Language\')->getLanguageId()'
		),
	);
	
	/**
	 * @param number $id
	 * @return multitype:
	 */
	public function get($id) {
		$db = \Core\Db\Init::getDefaultAdapter();
		$sql = $db->select()
					->from('color')
					->joinLeft('color_description', 'color.id = color_description.color_id', array('title','description','meta_title','meta_description','meta_keywords'))
					->where('color.id = ?', $id)
					->where('color_description.language_id = ?', \Core\Base\Action::getModule('Language')->getLanguageId());
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

	
}