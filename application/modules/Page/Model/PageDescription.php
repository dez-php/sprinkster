<?php

namespace Page;

class PageDescription extends \Base\Model\Reference {
	
	/*protected $_dependentTables = array(
			'\Page\Page',
			'\Language\Language'
	);*/

	protected $_referenceMap    = array(
			'Page' => array(
					'columns'           => 'page_id',
					'refTableClass'     => 'Page\Page',
					'refColumns'        => 'id',
					'singleRow'			=> true
			),
			'Language' => array(
					'columns'           => 'language_id',
					'refTableClass'     => 'Language\Language',
					'refColumns'        => 'id'
			),
	);
	
	
}

?>