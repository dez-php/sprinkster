<?php

namespace Conversation;

class SendController extends \Base\PermissionController {
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function indexAction() {
		
		$request = $this->getRequest();
		$userTable = new \User\User();

		$conversation_id = (int)$request->getPost('con_id');
		$to_user = $userTable->get((int)$request->getPost('to_user_id'));
		$self = \User\User::getUserData();
		$return = array();
		if($self->id && $conversation_id?true:$to_user) {
			$title_post = $this->escape($request->getPost('title'));
			$message_post = $this->escape($request->getPost('conversation'));
			$pin_id = (int)$request->getPost('pin_id');

			$validator = new \Core\Form\Validator(array(
					'translate' => $this->_
			));

			$conversation = null;
			$conversationTable = new \Conversation\Conversation();
			if($conversation_id) {
				if(($cn = $conversationTable->fetchRow($conversationTable->makeWhere(array('id' => $conversation_id)))) !== null && ($cn->user_id == $self->id || $cn->to_user_id == $self->id)) {
					$conversation = $cn;
					if($cn->user_id == $self->id) {
						$to_user = $userTable->get($cn->to_user_id);
					} else {
						$to_user = $userTable->get($cn->user_id);
					}
				}
			}

			if(!$conversation) {
				$validator->addText('title', array(
						'min' => 3,
						'error_text_min' => $this->_('Title must contain more than %d characters')
				));
			}
			$validator->addText('conversation', array(
					'min' => 3,
					'error_text_min' => $this->_('Message must contain more than %d characters')
			));
			
			if(!$to_user) {
				$validator->addText('conversationasdfggfg', array(
						'min' => 3,
						'error_text_min' => $this->_('Unable to send conversations!')
				));
			}
			
			if($validator->validate()) {
				$userTable->getAdapter()->beginTransaction();
				try {
					
					$guid1 = $this->guid($pin_id . '_' . $self->id . '_' . $to_user->id . '_' . $title_post);
					$guid2 = $this->guid($pin_id . '_' . $to_user->id . '_' . $self->id . '_' . $title_post);
					
					if(!$conversation) {
						if(($cn = $conversationTable->fetchRow($conversationTable->makeWhere(array('guid' => $guid1)))) !== null && (($cn->user_id == $self->id && $cn->to_user_id == $to_user->id) || ($cn->to_user_id == $self->id && $cn->user_id == $to_user->id))) {
							$conversation = $cn;
						} elseif(($cn = $conversationTable->fetchRow($conversationTable->makeWhere(array('guid' => $guid2)))) !== null && (($cn->user_id == $self->id && $cn->to_user_id == $to_user->id) || ($cn->to_user_id == $self->id && $cn->user_id == $to_user->id))) {
							$conversation = $cn;
						}
					}
					if(!$conversation) {
						$conversation = $conversationTable->fetchNew();
						$conversation->user_id = $self->id;
						$conversation->to_user_id = $to_user->id;
						$conversation->title = $title_post;
						$conversation->guid = $guid1;
						$conversation->save();
						$conversation_id = $conversation->id;
					} else {
						$conversation_id = $conversation->id;
					}
					
					$conversation->save();
					
					$conversationMessageTable = new \Conversation\ConversationMessage();
					$message = $conversationMessageTable->fetchNew();
					$message->conversation_id = $conversation_id;
					$message->conversation = $message_post;
					$message->user_id = $self->id;
					$message->to_user_id = $to_user->id;
					$message->save();
					$data['conversation'] = $message;
					$return['conversations'] = $this->render('index',$data, null, true);

					$userTable->getAdapter()->commit();
				} catch (\Core\Exception $e) {
					$userTable->getAdapter()->rollBack();
					$return['error'] = $e->getMessage();
				}
			} else {
				$return['error'] = implode('<br />', $validator->getErrors());
			}
		} else {
			$return['error'] = $this->_('Unable to send conversations!');
		}
		$this->responseJsonCallback($return);
		
	}
	
	protected function mentionsInput($text) {
		
		return $text;
	}
	
}