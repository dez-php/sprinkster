<?php

namespace Conversation;

class GetConversationController extends \Base\PermissionController {

	public function init() {
		$this->noLayout(true);
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() {
		
		$self = \User\User::getUserData();
		if(!$self->id) {
			$return['error'] = $this->_('There are no messages here.');
			$this->responseJsonCallback($return);
			exit;
		}
		
		$request = $this->getRequest();
		$conversation_id = $request->getPost('con_id');
		$last = (int)$request->getPost('last');
		$self = \User\User::getUserData();
		$return = array();
		
		$conversationsTable = new \Conversation\Conversation();
		if( ($conversation = $conversationsTable->fetchRow('id = ' . (int)$conversation_id . ' AND (user_id = ' . $self->id . ' OR to_user_id = ' . $self->id . ')'))!==null) {
			$return['conversation_id'] = $conversation->id;
			$update = false;
			if($request->getPost('check')) {
				if((int)$last) {
					$return['total'] = (new \Conversation\ConversationMessage())->countByConversationId_ToUserId_Read_Id($conversation->id,$self->id, 0,'>'.(int)$last);
				} else {
					$return['total'] = (new \Conversation\ConversationMessage())->countByConversationId_ToUserId_Read($conversation->id,$self->id, 0);
				}
				
			} else if($request->getPost('getnew')) {
				$data['conversations'] = (new \Conversation\ConversationMessage())->fetchAll(
					'conversation_id = ' . (int)$conversation->id , ' ( to_user_id = '.(int)$self->id.' OR user_id = '.(int)$self->id.') ' . ((int)$last ? (' AND id > ' . (int)$last) : '')
				);
				$update = true;
				$return['conversations'] = $this->render('index',$data, null, true);
			} else {
				$data['conversations'] = $conversation->Order('id DESC')->Messages();
				$update = true;
				$return['conversations'] = $this->render('index',$data, null, true);
			}
			
			if($update) {
				if($data['conversations']->count()) {
					if(!$conversation->read && $data['conversations'][0]->to_user_id == $self->id) {
						$conversation->read = 1;
						$conversation->save();
					}
					(new \Conversation\ConversationMessage())->update(array(
							'read' => 1
					), array('conversation_id = ?' => $conversation->id,'to_user_id = ?'=>$self->id,'`read` = ?' => 0));
					
				}
			}
		} else {
			$return['error'] = $this->_('There are no messages here.');
		}
		
		
		/*if($request->getPost('check')) {
			$conversationsTable = new \Message\Message();
			$return['total'] = $conversationsTable->fetchMessagesNewTotal($self->id,$user_id, $last);
		} else if($request->getPost('getnew')) {
			if($self->id && $user_id) {
				$conversationsTable = new \Message\Message();
				$data['conversations'] = $conversationsTable->fetchMessagesSend($self->id,$user_id, $last);
				if($data['conversations']->count()) {
					$conversationsTable->update(array(
							'read' => 1
					), array('user_id = ?' => $self->id,'from_user_id = ?' => $user_id,'`read` = ?'=>0));
				}
				$return['conversations'] = $this->render('index',$data, null, true);
			} else {
				$return['error'] = $this->_('There are no messages here.');
			}
		} else {
			if($self->id && $user_id) {
				$conversationsTable = new \Message\Message();
				
				$data['conversations'] = $conversationsTable->fetchMessages($self->id,$user_id, 2000);
				if($data['conversations']->count()) {
					$conversationsTable->update(array(
						'read' => 1
					), array('user_id = ?' => $self->id,'from_user_id = ?' => $user_id,'`read` = ?'=>0));
				}
				$return['conversations'] = $this->render('index',$data, null, true);
			} else {
				$return['error'] = $this->_('There are no messages here.');
			}
		}*/
		$this->responseJsonCallback($return);
	}
	
	protected function mentionsInput($text) {
		
		return $text;
	}
	
}