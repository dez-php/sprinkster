<?php

namespace Conversation;

class ReadController extends \Base\PermissionController {

	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function indexAction() {
		
		$request = $this->getRequest();
		
		$self = \User\User::getUserData();
		if(!$self->id) {
			$this->redirect( $this->url(array('controller' => 'login'),'user_c') );
		}
		
		$data['conversation_id'] = $request->getParam('conversation_id');
		
		$conversationsTable = new \Conversation\Conversation();
		if(!$conversationsTable->countBy('id = ' . (int)$data['conversation_id'] . ' AND (user_id = ' . $self->id . ' OR to_user_id = ' . $self->id . ')')) {
			$this->forward('index',null,'index');
		}
		
		$data['conversations'] = $conversationsTable->getAll(
				'(user_id = ' . $self->id . ' OR to_user_id = ' . $self->id . ')',
				new \Core\Db\Expr('FIELD(conversation.id,' . (int)$data['conversation_id'] . ') DESC,`read` ASC, date_modified DESC'),
				2000
		);
		
		
		
		$this->render('index', $data, array('controller'=>'index'));
	}
	
}