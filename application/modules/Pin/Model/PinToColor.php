<?php

namespace Pin;

class PinToColor extends \Base\Model\Reference {
	
	/**
	 * @param number $pin_id
	 * @return \Core\Db\Table\Rowset\AbstractRowset
	 */
	public function getAll($pin_id) {
		$db = \Core\Db\Init::getDefaultAdapter();
		
		$sql = $db->select()
					->from($this->_name,'')
					->joinLeft('color', 'color_id = color.id')
					->joinLeft('color_description', 'color.id = color_description.color_id', array('title','description','meta_title','meta_description','meta_keywords'))
					->where('pin_id = ?', $pin_id)
					->order('percent DESC')
					->where('color_description.language_id = ?', \Core\Base\Action::getModule('Language')->getLanguageId());

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
	
	public static function pinByColors($color_id) {
		$self = new self();
		return $self->select()->from($self,'pin_id')->where('color_id = ?', $color_id);
	}

	
	
}