<?php

namespace Conversation;

class DeleteController extends \Base\PermissionController {

	public function init() {
		$this->noLayout(true);
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() {
		
		$self = \User\User::getUserData();
		if(!$self->id) {
			$return['error'] = $this->_('Unable to delete!');
			$this->responseJsonCallback($return);
			exit;
		}
		
		$request = $this->getRequest();
		$conversation_id = $request->getPost('con_id');
		
		$conversationsTable = new \Conversation\Conversation();

		$conversations = $conversationsTable->fetchRow('id = '.(int)$conversation_id.' AND (user_id = ' . $self->id . ' OR to_user_id = ' . $self->id . ')');
		
		if($conversations) {
			$demo_user_id = \Base\Config::get('demo_user_id');
			if($demo_user_id && $demo_user_id == $self->id) {
				$return['error'] = $this->_('You don\'t have permissions for this action!');
			} else {
				try {
					$conversations->delete();
					$return['ok'] = true;
				} catch (\Core\Exception $e) {
					$return['error'] = $e->getMessage();
				}
			}
		} else {
			$return['error'] = $this->_('Unable to delete!');
		}
		
		$this->responseJsonCallback($return);
	}
	
	protected function mentionsInput($text) {
		
		return $text;
	}
	
}