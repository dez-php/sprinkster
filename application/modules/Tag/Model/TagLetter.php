<?php

namespace Tag;

class TagLetter extends \Base\Model\Reference {

	protected $_referenceMap    = array(
		'Tag' => array(
			'columns'           => 'id',
			'refTableClass'     => 'Tag\Tag',
			'refColumns'        => 'letter_id'
		),
	);
	
	public static function getByTag($tag, $in_menu = 0) {
		if(trim($tag) === '')
			return null;
		$letter = mb_strtoupper(mb_substr($tag, 0, 1, 'utf-8'), 'utf-8');
		if(in_array($letter, ['0','1','2','3','4','5','6','7','8','9']))
			$letter = '9';
		$self = new self();
		$result = $self->fetchRow([ 'letter LIKE ?' => $letter ]);
		if($result)
			return $result->id;
		return $self->insert([ 'letter' => $letter, 'in_menu' => $in_menu ]);
	}
	
}