<?php

namespace Language;

class Language extends \Base\Model\Reference {
	
	public function __construct($config = array()) {
		parent::__construct($config);
		$this->setRowClass('\Language\LanguageRow');
	}

	public static function getAll() {
		$db = \Core\Db\Init::getDefaultAdapter();
		$sql = $db->select()
					->from('language')
					->where('status = 1')
					->order('sort_order ASC');
		return $db->fetchAll($sql, array(), \Core\Db\Init::FETCH_OBJ);
	}
	
}