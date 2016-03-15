<?php

namespace Interest;

class IndexController extends \Base\PermissionController {
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() {	
		$request = $this->getRequest();
		
		$query = $request->getParam('query');
		$interestTable = new \Interest\Interest();
		$interest = $interestTable->fetchRow($interestTable->makeWhere(array(
			'query' => $query	
		)));

		$data['interest'] = $interest;
		
		if(!$interest) {
			$wordsAll = new \Core\Text\ParseWords($query);
			$words = $wordsAll->getMinLenght(1);
			
			$query = \Core\Camel::toCamelCase( implode('_', (array)$words), true, true );
			
			$data['query'] = $query;
			$data['pin_ids'] = $this->parseDescription($words);
		} else {
			$data['query'] = $interest->title;
		}
		
		//set page metatags
		$this->placeholder('meta_tags', $this->render('header_metas', $data,null,true));
		
		//render view
		$this->render('index', $data);
		
	}
	
	protected function parseDescription($words) {
		
		$indexTable = new \Search\SearchIndex();
		$dataWords = array();
		$adapter = $indexTable->getAdapter();
		foreach($words AS $word) {
			if(mb_strlen($word) < 2) {
				$dataWords[$word] = '`word` LIKE ' . $adapter->quote($word);
			} else {
				$dataWords[$word] = '`word` LIKE ' . $adapter->quote('%'.$word.'%');
			}
		}
		
		$pinIndexTable = new \Pin\PinSearchIndex();
		
		$sql = $indexTable->getAdapter()->select()
					->from('search_index','')
					->joinLeft('pin_search_index', 'search_index.id = pin_search_index.search_id',array('pin_id','pin_id'))
					->where(implode(' OR ', $dataWords))
					->limit(5000)
					->group('pin_id')
					->having('COUNT(DISTINCT search_index.id) >= ' . count($dataWords));

		$indexes = $indexTable->getAdapter()->fetchPairs($sql);
		return $indexes ? $indexes : array(0);
		
	}
	
	
}