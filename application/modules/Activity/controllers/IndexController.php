<?php

namespace Activity;

class IndexController extends \Core\Base\Action
{

	public $user;

	public function init()
	{
		$this->user = \User\User::getUserData();
		$this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function indexAction()
	{
		
		$data = [];
		
		if(!$this->user->id) {
			if($this->getRequest()->isXmlHttpRequest())
				return $this->responseJsonCallback([]);
			$this->redirect($this->url(array('controller' => 'login'), 'user_c'));
		}
		
		$delete = $this->getRequest()->getQuery('delete') == 'true';
		if($delete) {
			(new \Activity\Activity)->delete([ 'to_user_id = ?' => $this->user->id ]);
			$this->redirect( $this->url('activity') );
		}
		
		$limit = \Base\Config::get('pins_per_page');
		$page = (int)$this->getRequest()->getParam('page');
		if($page < 1)
			$page = 1;
		
		$where = [ 'to_user_id' => $this->user->id ];
		
		$activity = new \Activity\Activity;
		$data['activity'] = $activity->getAll($activity->makeWhere($where), 'id DESC', $limit, ($page * $limit) - $limit);
		$data['check'] = $this->check;
		
		if($this->getRequest()->isXmlHttpRequest())
			return $this->responseJsonCallback($this->render('index_ajax', $data, null, true));
			
		$this->render('index', $data);
	}

}