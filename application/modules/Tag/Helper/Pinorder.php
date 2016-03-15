<?php

namespace Tag\Helper;

class Pinorder extends \Pin\PinOrderAbstract {
	
	public function getExtendetSql() { 
		
		if(!\Core\Base\Action::getInstance()->isModuleAccessible('Tag'))
			return $this->sql;
		
		$pin_id = null;
		$where = $this->sql->getPart(\Core\Db\Select::WHERE);
		if($where) {
			foreach($where AS $w)
				if(preg_match('~\(pin\.id\s+=\s+[\'\"](\d{1,})[\'\"]\)~i',$w, $m))
					$pin_id = $m[1];
		}
		
		if((int)$pin_id && ($total_tags = (new \Tag\PinTag())->countByPinId($pin_id)) > 0) {
			$this->sql->columns([
				'pin_tags' => new \Core\Db\Expr('@pin_tags:=(SELECT GROUP_CONCAT(tag_id) FROM pin_tag WHERE pin.id = pin_id LIMIT 1)'),
				'pin_tags_related_total' => new \Core\Db\Expr('IF(@pin_tags,(SELECT COUNT(DISTINCT pin_id) FROM pin_tag WHERE pin_id != pin.id AND tag_id IN (SELECT tag_id FROM pin_tag WHERE pin.id = pin_id) LIMIT 1), 0)')
			]);
		} else {
			$this->sql->columns([
				'pin_tags' => new \Core\Db\Expr('""'),
				'pin_tags_related_total' => new \Core\Db\Expr('0')
			]);
		}
		
		return $this->sql;
	}
	
	
}

?>