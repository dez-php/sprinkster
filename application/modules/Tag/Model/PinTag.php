<?php

namespace Tag;

class PinTag extends \Base\Model\Reference {
	
	protected $_referenceMap    = [
			'Pin' => [
					'columns'           => 'pin_id',
					'refTableClass'     => 'Pin\Pin',
					'refColumns'        => 'id',
			],
			'Tag' => [
					'columns'           => 'tag_id',
					'refTableClass'     => 'Tag\Tag',
					'refColumns'        => 'id',
			],
	];
	
	public static function pinTagsCallback($tag_id)
	{
		$self = new self();
		return $self->select()->from($self,'pin_id')->where('tag_id = ?', $tag_id);
	}
	
	public static function getRelated($id, $total = null)
	{
		$db = \Core\Db\Init::getDefaultAdapter();
		
		$total = (int)$total > 0 ? MIN($total, 5000) : 5000;
		
		$sql = $db->select()
					->from(['p1' => 'pin_tag'], 'pin_id')
					->joinLeft(['p2' => 'pin_tag'], 'p1.tag_id = p2.tag_id', '')
					->where('p2.pin_id = ?', $id)
					->where('p1.pin_id != ?', $id)
					->limit($total); 

		return $db->select()->from(['pin_tag' => $sql]);	
	}
	
}