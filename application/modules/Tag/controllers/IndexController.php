<?php

namespace Tag;

class IndexController extends \Base\PermissionController {
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function indexAction() {
		
		$request = $this->getRequest();
		if($request->isXmlHttpRequest() && $request->issetPost('value')) {
			return $this->autocomplete($request->getPost('value'));
		}

		$data['letters'] = [];
		$temp = [];
		$letters = (new TagLetter)->fetchAll('in_menu = 1', 'sort_order ASC');
		foreach($letters AS $letter) {
			$temp[$letter->id] = $letter->letter;
			$data['letters'][] = [
				'id' => $letter->id,
				'letter' => $letter->letter,
				'title'  => $letter->letter != '9' ? $letter->letter : '(0-9)'
			];
		}
		
		$leter = $request->getRequest('leter');
		if(!$leter || !in_array($leter, $temp)) {
			if($temp)
				list($i, $l) = each($temp);
			$leter = isset($l) && $l ? $l : '-';
		}
		$data['letter_get'] = $leter;

		$tagTable = new \Tag\Tag();	
		$data['tags'] = null;
		if( ($letter_id = array_search($leter, $temp)) !== false ) 
			$data['tags'] = $tagTable->fetchByLeterWithPinCount($letter_id);
		
		$this->render('index', $data);
		
	}

	public function pinsAction() {
		$request = $this->getRequest();
		
		$tag_id = $request->getRequest('tag_id');
		
		$tagTable = new \Tag\Tag();
		$tag = $tagTable->fetchRow(array('id = ?' => $tag_id));
		if(!$tag) {
			$this->forward('error404');
		}
		
		$this->render('pins', array('tag' => $tag));
		
	}
	
	protected function autocomplete($value) {
		$this->noLayout(true);
		$value = trim($value);
		
		$response = array();
		if($value) {
			$tagTable = new \Tag\Tag();
			$tags = $tagTable->fetchAll(array('tag LIKE ?' => new \Core\Db\Expr($tagTable->getAdapter()->quote($value . '%'))),'tag ASC',300);
			foreach($tags AS $tag) {
				$response[] = array(
					'value' => html_entity_decode($tag['tag'], ENT_QUOTES, 'utf-8')	
				);
			}
		}
		
		$this->responseJsonCallback($response);
		
	}
	
}