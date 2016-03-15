<?php

namespace Interest\Event;

class Pinit {

	public function SetIndexes($pin_id) {
		$pinTable = new \Pin\Pin();
		$pin = $pinTable->fetchRow(array('id = ?' => $pin_id));
		if($pin) {
			
			$insert = array();
			$interestTable = new \Interest\Interest();
			$interestPinTable = new \Interest\InterestPin();
			$interestPinTable->delete(array('pin_id = ?' => $pin_id));
			//title
			$wordsAll = new \Core\Text\ParseWords( strip_tags(html_entity_decode($pin->title,ENT_QUOTES,'utf-8')) );
			$words = $wordsAll->getMinLenght(3);
			
			if($words && $words->count()) { 
				$words = array_unique((array)$words);
				$where = array();
				$words = array_map(function($word) use($interestTable) {
					$return = 'interest_tag.tag LIKE ' . $interestTable->getAdapter()->quote($word);
					if(strtolower(substr($word, -1)) == 's' && strlen(substr($word, -1)) >= 3) {
						$return .= ' OR interest_tag.tag LIKE ' . $interestTable->getAdapter()->quote(substr($word, 0, -1));
					} 
					return $return;
				}, $words);
				
				$like = '(' . implode(' OR ', $words) . ') AND interest.category_id = ' . $interestTable->getAdapter()->quote($pin->category_id);
				$sql = $interestTable->getAdapter()->select()
								->from('interest')
								->joinLeft('interest_tag', 'interest.id = interest_tag.interest_id','tag')
								->where($like);
								
				$query = $interestTable->getAdapter()->query($sql);
				$interests = array();
				while ( ($row = $query->fetch(\Core\Db\Init::FETCH_OBJ)) !== false ) {
					$interests[] = $row;
				}
				
				foreach($interests AS $interest) {
					$wordsAll = new \Core\Text\ParseWords( mb_strtolower($interest->tag, 'utf-8') );
					$title = array_unique((array)$wordsAll->getMinLenght(1));
					if($title) {
						$count = 0;
						$total = count($title);
						foreach($title AS $t) {
							if( mb_stripos($pin->title, $t) !== false ) {
								$count++;
							}
						}
						if($count >= $total) {
							$insert[$interest->id] = $interest->id;
						}		
					}
				}
			}
			
			//description
			$wordsAll = new \Core\Text\ParseWords( strip_tags(html_entity_decode($pin->description,ENT_QUOTES,'utf-8')) );
			$words = $wordsAll->getMinLenght(3);
				
			if($words && $words->count()) {
				$words = array_unique((array)$words);
				$where = array();
				$words = array_map(function($word) use($interestTable) {
					$return = 'interest_tag.tag LIKE ' . $interestTable->getAdapter()->quote($word);
					if(strtolower(substr($word, -1)) == 's' && strlen(substr($word, -1)) >= 3) {
						$return .= ' OR interest_tag.tag LIKE ' . $interestTable->getAdapter()->quote(substr($word, 0, -1));
					} 
					return $return;
				}, $words);
				
				$like = '(' . implode(' OR ', $words) . ') AND interest.category_id = ' . $interestTable->getAdapter()->quote($pin->category_id);
				$sql = $interestTable->getAdapter()->select()
								->from('interest')
								->joinLeft('interest_tag', 'interest.id = interest_tag.interest_id','tag')
								->where($like);
								
				$query = $interestTable->getAdapter()->query($sql);
				$interests = array();
				while ( ($row = $query->fetch(\Core\Db\Init::FETCH_OBJ)) !== false ) {
					$interests[] = $row;
				}
				foreach($interests AS $interest) {
					$wordsAll = new \Core\Text\ParseWords( mb_strtolower($interest->title, 'utf-8') );
					$title = array_unique((array)$wordsAll->getMinLenght(1));
					if($title) {
						$count = 0;
						$total = count($title);
						foreach($title AS $t) {
							if( mb_stripos($pin->description, $t) !== false ) {
								$count++;
							}
						}
						if($count >= $total) {
							$insert[$interest->id] = $interest->id;
						}
					}
				}
			}
			
			if($insert) {
				foreach($insert AS $interest_id) {
					$new = $interestPinTable->fetchNew();
					$new->interest_id = $interest_id;
					$new->pin_id = $pin_id;
					$new->save();
				}
			}
			
		}
	}
	
}