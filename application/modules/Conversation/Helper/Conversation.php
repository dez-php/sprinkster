<?php

namespace Conversation\Helper;

use Core\Base\Action;

class Conversation extends Action
{

	public function __construct()
	{
	}

	public static function sendMessage($to, $message_text, $title_post, $param, $from = null) {
		if(!$from) {
			$from = \User\User::getUserData();
		}

		$guid1 = self::guid($param . '_' . $from->id . '_' . $to . '_' . $title_post);

		$conversationTable = new \Conversation\Conversation();

		//Add conversation
		$conversation = $conversationTable->fetchNew();
		$conversation->user_id = $from->id;
		$conversation->to_user_id = $to;
		$conversation->title = $title_post;
		$conversation->guid = $guid1;
		$conversation->save();
		$conversation_id = $conversation->id;

		$conversation->save();

		//Add conversation message
		$conversationMessageTable = new \Conversation\ConversationMessage();
		$message = $conversationMessageTable->fetchNew();
		$message->conversation_id = $conversation_id;
		$message->conversation = $message_text;
		$message->user_id = $from->id;
		$message->to_user_id = $to;
		$message->save();
	}



}