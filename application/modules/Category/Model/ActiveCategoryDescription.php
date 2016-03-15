<?php

namespace Category;

class ActiveCategoryDescription extends \Core\Db\ActiveRecord {

    protected $_name = 'category_description';

	protected $_referenceMap    = array(
			'Category' => array(
					'columns'           => 'category_id',
					'refTableClass'     => 'Category\Category',
					'refColumns'        => 'id',
					'singleRow'			=> true
			),
	);
	
}

?>