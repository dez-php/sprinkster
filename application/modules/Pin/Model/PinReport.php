<?php

namespace Pin;

class PinReport extends \Base\Model\Reference {
	
	protected $_referenceMap    = array(
			'PinReportCategory' => array(
					'columns'           => 'report_category_id',
					'refTableClass'     => 'Pin\PinReportCategory',
					'referenceMap'		=> array(
						'columns'           => 'id',
						'refTableClass'     => 'Pin\PinReportCategoryDescription',
						'refColumns'        => 'report_category_id',
						'where'				=> '"language_id = " . \Core\Base\Action::getModule(\'Language\')->getLanguageId()'
					),
					'refColumns'        => 'id'
			),
			'User' => array(
					'columns'           => 'user_id',
					'refTableClass'     => 'User\User',
					'refColumns'        => 'id'
			),
			'Pin' => array(
					'columns'           => 'pin_id',
					'refTableClass'     => 'Pin\Pin',
					'refColumns'        => 'id'
			),
	);
	
	//virtual map for reference
	protected $_referenceReverseMap    = array(
			'User\User' => array(
					'columns'           => 'id',
					'refTableClass'     => 'Pin\PinReport',
					'refColumns'        => 'user_id',
					'singleRow'			=> true
			),
			'Pin\Pin' => array(
					'columns'           => 'id',
					'refTableClass'     => 'Pin\PinReport',
					'refColumns'        => 'pin_id',
					'singleRow'			=> true
			),
			'Pin\PinReportCategory' => array(
					'columns'           => 'id',
					'refTableClass'     => 'Pin\PinReport',
					'refColumns'        => 'report_category_id',
					'singleRow'			=> true
			),
	);
	
	
}