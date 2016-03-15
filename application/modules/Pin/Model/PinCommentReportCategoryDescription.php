<?php

namespace Pin;

class PinCommentReportCategoryDescription extends \Base\Model\Reference {

	protected $_referenceMap    = array(
			'PinCommentReportCategory' => array(
					'columns'           => 'comment_report_category_id',
					'refTableClass'     => 'Pin\PinCommentReportCategory',
					'refColumns'        => 'id',
					'singleRow'			=> true
			),
			'Language' => array(
					'columns'           => 'language_id',
					'refTableClass'     => 'Language\Language',
					'refColumns'        => 'id'
			),
			'PinCommentReport' => array(
					'columns'           => 'comment_report_category_id',
					'refTableClass'     => 'Pin\PinCommentReport',
					'refColumns'        => 'id',
					'singleRow'			=> true
			),
	);
	
	
}