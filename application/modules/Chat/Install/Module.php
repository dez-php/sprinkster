<?php

namespace Chat\Install;

class Module extends \Base\Install\Module {

	protected $config = [
		'chat_url' => [ 'group' => 'config', 'key' => 'chat_server_url', 'value' => 'localhost:10000', 'serialize' => 0, 'form_label' => 'Chat Server URL', 'form_type' => 'Text', 'form_list' => '', 'form_required' => '1', 'sort_order' => '99', 'form_helpMessage' => '' ],
		'token_duration' => [ 'group' => 'config', 'key' => 'chat_token_expiration_days', 'value' => 1, 'serialize' => 0, 'form_label' => 'Chat Session Expiration (in days)', 'form_type' => 'Number', 'form_list' => '', 'form_required' => '1', 'sort_order' => '99', 'form_helpMessage' => '' ],
	];

	protected $tables = [ 'chat_session' ];

	public function install()
	{
		$sql = file_get_contents(__DIR__ . '/sql.sql');

		$parser = new \Core\Db\Schema\Parser();
		$queries = $parser->delta($sql, false, false);

		if(isset($queries['dbh_global']))
			foreach($queries['dbh_global'] as $table => $query)
				foreach ($query as $q)
					$this->query($q['query']);

		$table = new \Chat\Session;
		$table->createFK('user_id', 'user', 'id', 'CASCADE');

		// Apply module configuration
		foreach($this->config AS $c)
			if(!$this->existRecord('config', 'key', $c['key']))
				$this->execute('config', $c);

		// Before Footer
		if(!$this->existRecord('menu', [ 'guid' => '86a32aa8-0f50-fc70-e83f-dc040c79e87' ])) {
			$this->appendMenu([
					'widget' => 'chat.widget.chat',
					'config' => '',
					'is_group' => 0,
					'is_widget' => 1,
					'title' => 'Chat',
					'route' => '',
					'group_id' => 'FooterExtensions',
					'status' => 1,
					'parent_id' => NULL,
					'module' => 'chat',
					'guid' => '86a32aa8-0f50-fc70-e83f-dc040c79e87'
			]);
		}

		$this->addPermissions();
	}

	public function uninstall()
	{
		$this->deleteRecord('menu', [ 'module' => 'chat' ]);

		foreach ($this->config AS $c)
			if ($this->existRecord('config', 'key', $c['key']))
				$this->deleteRecord('config', [ 'key' => $c['key'] ]);
	}

}