<?php

namespace Category;

class ActiveCategory extends \Core\Db\ActiveRecord {

    protected $_name = 'category';

    protected $_referenceMap    = [
        'Description' => [
            'columns'           => 'id',
            'refTableClass'     => 'Category\ActiveCategoryDescription',
            'refColumns'        => 'category_id',
            'where'				=> '"language_id = " . \Core\Base\Action::getModule(\'Language\')->getLanguageId()'
        ],
    ];

}