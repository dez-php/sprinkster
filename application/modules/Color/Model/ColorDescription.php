<?php

namespace Color;

class ColorDescription extends \Base\Model\Reference {

	protected $_referenceMap    = array(
			'Color' => array(
					'columns'           => 'color_id',
					'refTableClass'     => 'Color\Color',
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