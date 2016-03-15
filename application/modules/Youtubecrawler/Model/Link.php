<?php

namespace Youtubecrawler;

class Link extends \Base\Model\Reference {

	protected $_name = 'crawler_youtube_links';
	
	protected $_referenceMap    = array(
			'Pin' => array(
					'columns'           => 'pin_id',
					'refTableClass'     => 'Pin\Pin',
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