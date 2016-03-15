<?php

namespace Interest\Event;

class SetIndexes {
	
	public function Indexes($interest_id) { 
		set_time_limit(0);
		ignore_user_abort(true);
		
		$interestTable = new \Interest\Interest();
		$interestPinTable = new \Interest\InterestPin();
		$interest = $interestTable->fetchRow(array('id = ?' => (int)$interest_id));
		
		$m56 = \Base\Config::get('database_fulltext_status') && version_compare($interestTable->getAdapter()->getServerVersion(), '5.6', '>=');
		
		if($interest) {
			$interestPinTable->delete(array('interest_id = ?' => $interest_id));
			$tags = $interest->Tags();
			//var_dump($tags->toArray(), $interest->Relateds()->Tags()->toArray()); exit;
			if($tags->count()) {
				foreach($tags AS $tag) {
					$wordsAll = new \Core\Text\ParseWords($tag->tag);
					$words = $wordsAll->getMinLenght(3);
					$wordsOne = $wordsAll->getMinLenght(1);
					$indexTable = new \Search\SearchIndex();
					$dataWords = array();
					if($words) {
						$adapter = $indexTable->getAdapter();
						foreach($words AS $word) {
							$dataWords[$word] = '`word` LIKE ' . $adapter->quote($word);
							if(strtolower(substr($word, -1)) == 's' && strlen(substr($word, -1)) >= 3) {
								$dataWords[substr($word, 0, -1)] = '`word` LIKE ' . $adapter->quote(substr($word, 0, -1));
							}
						}
					
						$pinIndexTable = new \Pin\PinSearchIndex();
						
						if($m56) {
							$sql = $indexTable->getAdapter()->select()
										->from('pin',array('id','id'))
										->where('MATCH(`pin`.`title`,`pin`.`description`) AGAINST (? IN NATURAL LANGUAGE MODE)', $tag->tag)
										->limit(5000);
						} else {
							$sql = $indexTable->getAdapter()->select()
										->from('search_index','')
										->joinLeft('pin_search_index', 'search_index.id = pin_search_index.search_id',array('pin_id','pin_id'))
										->where(implode(' OR ', $dataWords))
										->limit(5000)
										->group('pin_id')
										->having('COUNT(DISTINCT search_index.id) >= ' . count($dataWords));
						}
					
						$indexes = $indexTable->getAdapter()->fetchPairs($sql);
						$indexes = array_filter($indexes);
						
						if($indexes) {
							$pinTable = new \Pin\Pin();
							$weDe = $weTi = array();
							foreach($wordsOne AS $w) {
								$weDe[] = 'description LIKE ' . $pinTable->getAdapter()->quote('%' . $w . '%');
								$weTi[] = 'title LIKE ' . $pinTable->getAdapter()->quote('%' . $w . '%');
							}
						
							$wd = implode(' AND ', $weDe);
							$wt = implode(' AND ', $weTi);
							$pins = $pinTable->fetchAll($pinTable->makeWhere(array(
									'id' => $indexes,
									'where' => '((' . $wd . ') OR (' . $wt . '))',
									'category_id' => $interest->category_id
							)), null, 10000);
						
							foreach($pins AS $pin) {
								$new = $interestPinTable->fetchNew();
								$new->interest_id = $interest_id;
								$new->pin_id = $pin->id;
								$new->save();
							}
						}
							
					}
				}
			}
			
		}
		
	}
	
}