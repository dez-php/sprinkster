<?php

namespace Pin;

class PinCommentReport extends \Base\Model\Reference {
	
	protected $_referenceMap    = array(
			'PinCommentReportCategory' => array(
					'columns'           => 'comment_report_category_id',
					'refTableClass'     => 'Pin\PinCommentReportCategory',
					'referenceMap'		=> array(
						'columns'           => 'id',
						'refTableClass'     => 'Pin\PinCommentReportCategoryDescription',
						'refColumns'        => 'comment_report_category_id',
						'where'				=> '"language_id = " . \Core\Base\Action::getModule(\'Language\')->getLanguageId()'
					),
					'refColumns'        => 'id'
			),
			'User' => array(
					'columns'           => 'user_id',
					'refTableClass'     => 'User\User',
					'refColumns'        => 'id'
			),
			'Comment' => array(
					'columns'           => 'comment_id',
					'refTableClass'     => 'Pin\PinComment',
					'refColumns'        => 'id'
			),
	);
	
	//virtual map for reference
	protected $_referenceReverseMap    = array(
			'User\User' => array(
					'columns'           => 'id',
					'refTableClass'     => 'Pin\PinCommentReportCategory',
					'refColumns'        => 'user_id',
					'singleRow'			=> true
			),
			'Pin\PinComment' => array(
					'columns'           => 'id',
					'refTableClass'     => 'Pin\PinCommentReportCategory',
					'refColumns'        => 'comment_id',
					'singleRow'			=> true
			),
			'Pin\PinCommentReportCategory' => array(
					'columns'           => 'id',
					'refTableClass'     => 'Pin\PinCommentReport',
					'refColumns'        => 'comment_report_category_id',
					'singleRow'			=> true
			),
	);
	
	
}