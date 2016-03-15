<?php

namespace Pin\Event;

class Search {
	
	public function setIndexes($pin_id) {
		$pinTable = new \Pin\Pin();
		$pin = $pinTable->fetchRow(array('id = ?' => $pin_id));
		if($pin) {
			$searchIndex = new \Search\SearchIndex();
			$pinSearchIndex = new \Pin\PinSearchIndex();
			$pinSearchIndex->delete(array('pin_id = ?' => $pin->id));
			$wordsAll = new \Core\Text\ParseWords( html_entity_decode($pin->title . ' ' . $pin->description, ENT_QUOTES, 'utf-8') );
			$words = $wordsAll->getMinLenght(3);
			foreach($words AS $word) {
				if( ($row = $searchIndex->fetchRow(array('word = ?' => $word))) === null ) {
					$row = $searchIndex->fetchNew();
					$row->word = $word;
					$row->save();
				}
				if($row->id) {
					$pinSearchIndex->insert(array(
							'pin_id' => $pin->id,
							'search_id'	=>$row->id
					));
				}
			}
		}
	}
}