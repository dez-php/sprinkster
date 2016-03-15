<?php

namespace Conversation;

class IndexController extends \Base\PermissionController {
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function indexAction() {
		
		$request = $this->getRequest();
		
		$self = \User\User::getUserData();
		if(!$self->id) {
			$this->redirect( $this->url(array('controller' => 'login'),'user_c') );
		}
		
		$conversationsTable = new \Conversation\Conversation();
		$data['conversations'] = $conversationsTable->getAll(
			'(user_id = ' . $self->id . ' OR to_user_id = ' . $self->id . ')',
			new \Core\Db\Expr('`read` ASC, date_modified DESC'),
			2000
		);

		$data['conversation_id'] = $request->getParam('conversation_id');

		$this->render('index', $data);
	}
	
}