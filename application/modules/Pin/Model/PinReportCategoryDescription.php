<?php

namespace Pin;

class PinReportCategoryDescription extends \Base\Model\Reference {

	protected $_referenceMap    = array(
			'PinReportCategory' => array(
					'columns'           => 'report_category_id',
					'refTableClass'     => 'Pin\PinReportCategory',
					'refColumns'        => 'id',
					'singleRow'			=> true
			),
			'Language' => array(
					'columns'           => 'language_id',
					'refTableClass'     => 'Language\Language',
					'refColumns'        => 'id'
			),
			'PinReport' => array(
					'columns'           => 'report_category_id',
					'refTableClass'     => 'Pin\PinReport',
					'refColumns'        => 'id',
					'singleRow'			=> true
			),
	);
	
	
}