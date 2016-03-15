<?php

namespace Translate;

class TranslateData extends \Base\Model\Reference {
	
	protected $_referenceMap    = array(
			'Language' => array(
					'columns'           => 'language_id',
					'refTableClass'     => '\Language\Language',
					'refColumns'        => 'id'
			),
			'Translate' => array(
					'columns'           => 'translate_id',
					'refTableClass'     => 'Translate\Translate',
					'refColumns'        => 'id',
					'singleRow'			=> true
			),
	);

}