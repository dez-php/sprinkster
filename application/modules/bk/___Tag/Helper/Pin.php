<?php

namespace Tag\Helper;

class Pin {

	public function __construct($options) {
                if(isset($options['pin_id'])) {
			$pin_id = $options['pin_id'];
		} else {
			return $this;
		}
		$tagsExplode = \Core\Http\Request::getInstance()->getPost('extended[tags]');
		if(!is_array($tagsExplode))
			$tagsExplode = [];
		
		$tagTable = new \Tag\Tag();
		$pinTagTable = new \Tag\PinTag();
		$pinTagTable->delete(array('pin_id = ?' => $pin_id));
		
		$tags = array();
		foreach($tagsExplode AS $tag) {
			$tags[ mb_strtolower($tag) ] = trim($tag);
		}
		
		/* from description */
// 		$pinTable = new \Pin\Pin();
// 		$pin = $pinTable->fetchRow(array('id = ?' => $pin_id));
// 		if($pin) {
// 			$text = ' ' . html_entity_decode($pin->title . ' ' . $pin->description, ENT_QUOTES, 'utf-8') . ' ';
// 			if(trim($text)) {
// 				if( preg_match_all('~\#([^ \<\>]*)~i', $text, $matches)) {
// 					foreach($matches[0] AS $tag) {
// 						$tag = ltrim($tag, '#');
// 						if(!isset($tags[ mb_strtolower($tag) ])) {
// 							$tags[ mb_strtolower($tag) ] = $tag;
// 						}
// 					}
// 				}
// 			}
// 		}
		/* end from description */
		
		$inserted = array();
		$action = \Core\Base\Action::getInstance();
		foreach($tags AS $tag) {
			$tag = trim($tag);
			if($tag) {
				$tagData = $tagTable->fetchRow(array('tag LIKE ?' => $tag));
				if(!$tagData) {
					$tagData = $tagTable->fetchNew();
					$tagData->tag = $action->escape($tag);
					$tagData->save();
				}
				if($tagData->id) {
					$pinTag = $pinTagTable->fetchNew();
					$pinTag->pin_id = $pin_id;
					$pinTag->tag_id = $tagData->id;
					$pinTag->save();
					$inserted[$tag] = $pinTag->id;
				}
			}
		}
		foreach($inserted AS $tag => $res) {
			if(!$res) {
				if($tag) {
					$tagData = $tagTable->fetchRow(array('tag LIKE ?' => $tag));
					if(!$tagData) {
						$tagData = $tagTable->fetchNew();
						$tagData->tag = $action->escape($tag);
						$tagData->save();
					}
					if($tagData->id) {
						$pinTag = $pinTagTable->fetchNew();
						$pinTag->pin_id = $pin_id;
						$pinTag->tag_id = $tagData->id;
						$pinTag->save();
						$inserted[$tag] = $pinTag->id;
					}
				}
			}
		}
	}

}