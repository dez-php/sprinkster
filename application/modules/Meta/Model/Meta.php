<?php

namespace Meta;

class Meta extends \Base\Model\Reference {
	
	protected $_referenceMap    = array(
		'MetaDescription' => array(
			'columns'           => 'id',
			'refTableClass'     => 'Meta\MetaDescription',
			'refColumns'        => 'meta_id',
			'where'				=> '"language_id = " . \Core\Base\Action::getModule(\'Language\')->getLanguageId()'
		),
	);
	
	protected static $dataGet;
	
	public static function getGlobal($key, $language_id = null) {
		$language_id = $language_id ? $language_id : \Core\Base\Action::getModule('Language')->getLanguageId();
		if(!isset($dataGet['global'][$language_id][$key])) {
			$self = new self();
			$sql = $self->getAdapter()->select()
							->from('meta', '')
							->joinLeft('meta_description', 'meta.id = meta_description.meta_id', array('title','meta_title','meta_description','meta_keywords'))
							->where('meta.module = ?', 'global')
							->where('meta_description.language_id = ?', $language_id)
							->limit(1);
	
			$row = $self->getAdapter()->fetchRow($sql);
			if($row) {
				foreach($row AS $k=>$v) {
					$dataGet['global'][$language_id][$k] = $v;
				}
			}
		}
		return isset($dataGet['global'][$language_id][$key])?$dataGet['global'][$language_id][$key]:null;
	}
	
}