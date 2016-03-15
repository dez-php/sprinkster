<?php

namespace Conversation\Install;

class Module extends \Base\Install\Module {

	public function install() {
		$sql = file_get_contents(__DIR__.'/sql.sql');
		$parser = new \Core\Db\Schema\Parser();
		$queries = $parser->delta( $sql, false, false);
		
		if(isset($queries['dbh_global'])) {
			foreach ($queries['dbh_global'] AS $table => $queris) {
				foreach($queris AS $q) {
					$this->query($q['query']);
				}
			}
		}
		
		// User Tab Menu
		if(!$this->existRecord('menu', [ 'guid' => '41e47564-b2e2-6a5a-05b5-dc040c79e87' ]))
		{
			$max = $this->getMax('sort_order', 'menu', [ 'group_id' => 'UserTabs' ]);
			$max = (int)$max ? ((int)$max + 1) : 0;

			$this->execute('menu', [
				'sort_order' => $max,
				'widget' => 'conversation.widget.header',
				'config' => '',
				'is_group' => 0,
				'is_widget' => 1,
				'title' => 'Messages',
				'route' => '',
				'group_id' => 'UserTabs',
				'status' => 1,
				'parent_id' => NULL,
				'module' => 'conversation',
				'guid' => '41e47564-b2e2-6a5a-05b5-dc040c79e87'
			]);
		}
		
		//user profile
		if(!$this->existRecord('menu', array('guid' => 'b93967b8-23bc-d8c3-9c91-dc040c79e87'))) {
			$max = $this->getMax('sort_order', 'menu', array('group_id' => 'profileLink'));
			$max = (int)$max ? ((int)$max + 1) : 0;
			$this->execute('menu', array(
				'sort_order' => $max,
				'widget' => 'conversation.widget.send',
				'config' => '',
				'is_group' => 0,
				'is_widget' => 1,
				'title' => 'Send conversation',
				'route' => '',
				'group_id' => 'profileLink',
				'status' => 1,
				'parent_id' => null,
				'module' => 'conversation',
				'guid' => 'b93967b8-23bc-d8c3-9c91-dc040c79e87'
			));
		}
		
// 		if(!$this->existRecord('menu', array('guid'=>'613f550e-a02d-f3cd-ae24-dc040c79e87'))) {
// 			$max = $this->getMax('sort_order', 'menu', array('group_id'=>'PinActions'));
// 			$max = (int)$max ? ((int)$max + 1) : 0;
// 			$this->execute('menu', array(
// 					'sort_order' => $max,
// 					'widget' => 'conversation.widget.pinsend',
// 					'config' => '',
// 					'is_group' => 0,
// 					'is_widget' => 1,
// 					'title' => 'Send conversation to user',
// 					'route' => '',
// 					'group_id' => 'PinActions',
// 					'status' => 1,
// 					'parent_id' => null,
// 					'disabled' => 'return !(\User\User::getUserData()->id && \User\User::getUserData()->id != $data->user_id && @$config[\'template\'] == \'view\');',
// 					'module' => 'conversation',
// 					'guid' => '613f550e-a02d-f3cd-ae24-dc040c79e87'
// 			));
// 		}
		
		if(!$this->existRecord('menu', array('guid'=>'613f550e-a02d-f3cd-ae24-dc040c79e87'))) {
			$this->appendMenu(array(
					'widget' => 'conversation.widget.pinsend',
					'config' => '',
					'is_group' => 0,
					'is_widget' => 1,
					'title' => 'Send conversation to user',
					'route' => '',
					'group_id' => 'PinUserTop',
					'status' => 1,
					'parent_id' => null,
					'disabled' => 'return !(\User\User::getUserData()->id && $data && \User\User::getUserData()->id != $data->id);',
					'module' => 'conversation',
					'guid' => '613f550e-a02d-f3cd-ae24-dc040c79e87'
			));
		}
		
		if(!$this->existRecord('menu', array('guid'=>'35d50e12-e14d-1c0b-57f6-dc040c79e87'))) {
			$this->appendMenu(array(
					'widget' => 'conversation.widget.profilebuttonbar',
					'config' => '',
					'is_group' => 0,
					'is_widget' => 1,
					'title' => 'Send conversation to store',
					'route' => '',
					'group_id' => 'storeProfileButtonBar',
					'status' => 1,
					'parent_id' => null,
					'disabled' => 'return !(\User\User::getUserData()->id && $data && \User\User::getUserData()->id != $data->id);',
					'module' => 'conversation',
					'guid' => '35d50e12-e14d-1c0b-57f6-dc040c79e87'
			));
		}
		
		$fk = new \Conversation\Conversation();
		$fk->createFK('user_id', 'user', 'id', 'CASCADE', 'NO ACTION', 'fk_user_id_user_id');
		$fk->createFK('to_user_id', 'user', 'id', 'CASCADE', 'NO ACTION', 'fk_to_user_id_user_id');
		
		$fk = new \Conversation\ConversationMessage();
		$fk->createFK('conversation_id', 'conversation', 'id', 'CASCADE');
		$fk->createFK('user_id', 'user', 'id', 'CASCADE');
		
	}
	
	public function uninstall() {
		$this->deleteRecord('menu', array('module' => 'conversation'));
	}
	
	public function delete() {
		$this->query('SET FOREIGN_KEY_CHECKS=0;');
		$this->query('DROP TABLE IF EXISTS `conversation`');
		$this->query('DROP TABLE IF EXISTS `conversation_message`');
		$this->query('SET FOREIGN_KEY_CHECKS=1;');
	}
}