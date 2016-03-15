<?php

namespace Translate;

class AdminController extends \Core\Base\Action {
	
public function init() {
		if(!\User\User::getUserData()->is_admin) { $this->redirect($this->url([],'admin_login')); }
		$this->_ = new \Translate\Locale('Backend\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() {
		
		$data['namespaces'] = \Translate\Translate::getGroups();
		$this->render('index',$data);
	}
	
	public function groupAction() {
		$request = $this->getRequest();
		$data['namespaces'] = \Translate\Translate::getGroup($request->getQuery('group'));
		$data['languages'] = self::getModule('Language')->getLanguages();
		$data['group'] = $request->getQuery('group');
		$this->render('group',$data);
	}
	
	public function translateAction() {
		$request = $this->getRequest();
		$data['namespaces'] = \Translate\Translate::getGroupData($request->getQuery('group'));
		$data['languages'] = self::getModule('Language')->getLanguages();
		$data['group'] = $request->getQuery('group');
		$this->render('translate',$data);
	}

    public function searchAction() {
        $request = $this->getRequest();
        $data['namespaces'] = \Translate\Translate::getGroupDataSearch($request->getQuery('group'), $request->getQuery('search'));
        $data['languages'] = self::getModule('Language')->getLanguages();
        $data['group'] = $request->getQuery('group');
        $this->render('search',$data);
    }
	
	public function settranslateAction() {
		$request = $this->getRequest();
		$id = explode('_',$request->getPost('id'));
		$value = $request->getPost('value');
		$data = array();
		if(count($id)==2) {
			$TranslateDataTable = new \Translate\TranslateData();
			$TranslateData = $TranslateDataTable->fetchRow($TranslateDataTable->makeWhere(array(
				'translate_id' => $id[1],
				'language_id' => $id[0]		
			)));
			if(!$TranslateData) {
				$TranslateData = $TranslateDataTable->fetchNew();
				$TranslateData->translate_id = $id[1];
				$TranslateData->language_id = $id[0];
			}
			$TranslateData->message = trim($value);
			try {
				$demo_user_id = \Base\Config::get('demo_user_id');
				if($demo_user_id && $demo_user_id == \User\User::getUserData()->id) {
					$data['error'] = $this->_('You don\'t have permissions for this action!');
				} else {
					if($TranslateData->message) {
						$TranslateData->save();
					} else {
						$TranslateData->delete();
					}
				}
				$data['message'] = $TranslateData->message;
			} catch (\Core\Exception $e) {
				$data['error'] = $e->getMessage();
			}
		} else {
			$data['error'] = $this->_('Missing parameters!');
		}
		$this->responseJsonCallback($data);
	}
	
}