<?php

namespace Tag\Rest;

class Api extends \Rest\IndexController {

    protected $methodRequest = array(
        'post' => array(
        ),
        'get' => array(
            'find',
            'find-by',
            'find-by-name',
        	'get-letters'
        )
    );
    protected $allowedActions = array(
        'find',
        'find-by',
        'find-by-name'
    );

    public function getLettersAction($return = false) {
    	$lettersJson = [];
    	$letters = (new \Tag\TagLetter)->fetchAll('in_menu = 1', 'sort_order ASC');
    	foreach($letters AS $letter) {
    		$lettersJson[] = [
	    		'id' => $letter->id,
	    		'letter' => $letter->letter,
	    		'title'  => $letter->letter != '9' ? $letter->letter : '(0-9)'
    		];
    	}
    	if($return) 
    		return $lettersJson;
    	echo json_encode($lettersJson);
    }
    
    public function findByAction() {
        $request = $this->getRequest();
        $params = $request->getParams();
        $letter = (isset($params['letter'])) ? trim($params['letter']) : null;
        if($letter === null) {
        	$ltr = $this->getLettersAction(true);
        	if($ltr) {
        		$letter = $ltr[0]['id'];
        	}
        }
        
        if($letter === null) {
        	echo json_encode($this->error['tags_not_found']);
        	exit;
        }
        $letter = 'a';
        if( !preg_match('~^[0-9]$~', $letter) ) {
        	$letters = (new \Tag\TagLetter)->fetchRow(['letter LIKE ?' => $letter]);
        	if($letters) {
        		$letter = $letters->id;
        	} else {
        		echo json_encode($this->error['tags_not_found']);
        		exit;
        	}
        }

        $tagTable = new \Tag\Tag();
        $tags = $tagTable->fetchByLeterWithPinCount($letter);
        if (!empty($tags)) {
            echo json_encode($tags->toArray());
        } else {
            echo json_encode($this->error['tags_not_found']);
            exit;
        }
    }
    
    public function findAction() {
        $request = $this->getRequest();
        $params = $request->getParams();
        
        if (!isset($params['tag_id'])) {
            echo json_encode($this->error['missing_tag_id']);
            exit;
        }
        
        $tagTable = new \Tag\Tag();
		$tag = $tagTable->fetchRow(array('id = ?' => $params['tag_id']))->toArray();
        $select = \Tag\PinTag::pinTagsCallback($tag['id']);
        
        $pinTable = new \Pin\Pin();
        $result = $pinTable->fetchAll($pinTable->makeWhere(array('id' => $select)))->findMapData('User\User');
        if ($result->count()) {
            $pins = array();
            foreach ($result as $key => $pin) {
                $pins[$key] = \Rest\Pin::getImages($pin);
                $pins[$key]['user'] = \Rest\User::getAvatars($pin['user\user']);
                unset($pins[$key]['user\user']);
            }
            echo json_encode($pins);
        } else {
            echo json_encode($this->error['pin_not_found']);
            exit;
        }
    }
    
    public function findByNameAction() {
        $request = $this->getRequest();
        $params = $request->getParams();
        
        if (!isset($params['query'])) {
            echo json_encode($this->error['missing_query']);
            exit;
        }
        
        $db = \Core\Db\Init::getDefaultAdapter();
        $sql = $db->select()
            ->from('tag')
            ->where('tag LIKE ?', $params['query'].'%');
        $tags = $db->fetchAll($sql, [], \Core\Db\Init::FETCH_ASSOC);
        echo json_encode($tags);
    }

}
