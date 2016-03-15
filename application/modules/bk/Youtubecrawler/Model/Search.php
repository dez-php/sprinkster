<?php

namespace Youtubecrawler;

class Search extends \Base\Model\Reference {

	protected $_name = 'crawler_youtube_search';
	
	protected $_referenceMap    = array(
		'User' => array(
				'columns'           => 'user_id',
				'refTableClass'     => 'User\User',
				'refColumns'        => 'id'
		),
		'Category' => array(
				'columns'           => 'category_id',
				'refTableClass'     => 'Category\Category',
				'referenceMap'		=> array(
					'columns'           => 'id',
					'refTableClass'     => 'Category\CategoryDescription',
					'refColumns'        => 'category_id',
					'where'				=> '"language_id = " . \Core\Base\Action::getModule(\'Language\')->getLanguageId()'
				),
				'refColumns'        => 'id'
		),
	);

	
}