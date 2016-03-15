<?php

namespace Category;

class CategoryDescription extends \Base\Model\Reference {
	
	/*protected $_dependentTables = array(
			'\Category\Category',
			'\Language\Language'
	);*/

	protected $_referenceMap    = array(
			'Category' => array(
					'columns'           => 'category_id',
					'refTableClass'     => 'Category\Category',
					'refColumns'        => 'id',
					'singleRow'			=> true
			),
			'Language' => array(
					'columns'           => 'language_id',
					'refTableClass'     => 'Language\Language',
					'refColumns'        => 'id'
			),
			'Pin' => array(
					'columns'           => 'category_id',
					'refTableClass'     => 'Pin\Pin',
					'refColumns'        => 'category_id'
			),
	);
	
}

?>