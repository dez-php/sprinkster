<?php

namespace Conversation\Widget;

class Header extends \Base\Widget\UserTabWidget {

	protected $tab_id = 'messages';

	public function init() {
		$this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function tab()
	{
		$data = [];

		if(!$this->user->id)
			return;

		$data['total'] = (new \Conversation\ConversationMessage)->countByToUserId_Read($this->user->id, 0);

		$dir = $this->getComponent('Alias')->get($this->getNamespace()) . '/asset/';
		$document = $this->getComponent('document');
		$asset = $this->getComponent('AssetManager');
		$asset->publish($dir);
		$document->addScriptFile($asset->getPublishedUrl($dir) . '/js/linkify.js');
		
		$this->render('tab', $data);
	}

	public function content()
	{
		$data = [];

		if(!$this->user->id)
			return;

		$data['user'] = $this->user;
		// $data['conversations'] = (new \Conversation\Conversation)->fetchAll('(user_id = ' . $this->user->id . ' OR to_user_id = ' . $this->user->id . ')', new \Core\Db\Expr('`read` ASC, date_modified DESC'), 2000);

		$data['conversations'] = (new \Conversation\Conversation)->getAll('(user_id = ' . $this->user->id . ' OR to_user_id = ' . $this->user->id . ')', new \Core\Db\Expr('`read` ASC, date_modified DESC'), 20);

		$this->render('tabcontent', $data);
	}

}