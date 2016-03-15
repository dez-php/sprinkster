<?php

namespace Pin;

class LikeController extends \Base\PermissionController {
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() {
		$request = $this->getRequest();

    	//login popup
    	$self = \User\User::getUserData();
    	if (!$self->id)
    		$this->forward(
    				'popup', [
    					'url' => $this->url($request->getParams()),
    					'action' => 'like'
    				],
    				'login',
    				'user');
    	//end login popup
		
		if(!$request->isXmlHttpRequest())
			$this->forward('error404');
		
		$data = array();
		$pin = (new \Pin\Pin)->get($request->getRequest('pin_id'));

		if(!$pin || !$pin->id)
            $this->forward('error404');

		 // Disable repinning of own items
        if($self->id && $self->id === $pin->user_id)
            $this->forward('error404');

		if($self->id) {
			$pinLikeTable = new \Pin\PinLike();
			$pinLikeTable->getAdapter()->beginTransaction();
			try {
				$row = $pinLikeTable->fetchRow($pinLikeTable->makeWhere(array('user_id'=>$self->id,'pin_id'=>$request->getRequest('pin_id'))));
				
				if($row) {
					try {
						//remove activity
						$pinTable = new \Pin\Pin();
						$userTable = new \User\User();
						$user_info = $userTable->fetchRow($userTable->makeWhere(array('id'=>array($pinTable->select()->from($pinTable,'user_id')->where('id = ?',$row->pin_id)))));
						if($user_info) {
							\Activity\Activity::remove($user_info->id, 'LIKEPIN',$row->pin_id);
						}
						if($row->delete()) {
							$data['falses'] = true;
						} else {
							$data['error'] = 'error unlike';
						}
					} catch (\Core\Exception $e) {
						$data['error'] = $e->getMessage();
					}
				} else {
					$row = $pinLikeTable->fetchNew();
					$row->pin_id = $request->getRequest('pin_id');
					$row->user_id = $self->id;
					$row->date_added = \Core\Date::getInstance(null,\Core\Date::SQL_FULL,true)->toString();
					if($row->save()) {
						$userTable = new \User\User();
						$pinTable = new \Pin\Pin();
						$user_info = $userTable->fetchRow($userTable->makeWhere(array('id'=>array($pinTable->select()->from($pinTable,'user_id')->where('id = ?',$row->pin_id)))));
						if($user_info && $self->id != $user_info->id) {
							
							\Core\Http\Thread::run(
								[ '\Pin\Helper\LikeEmail', 'result' ],
								[
									'notify' => $user_info->notification_like_pin,

									'id' => $user_info->id,
									'email' => $user_info->email,
									'language_id' => $user_info->language_id,
									'firstname' => $user_info->firstname,
									'lastname' => $user_info->lastname,
									'username' => $user_info->username,
									'fullname' => $user_info->getUserFullName(),
									'author_fullname' => $self->getUserFullName(),
								],
								$row->pin_id,
								$this->url([ 'pin_id' => $row->pin_id ], 'pin'),
								$this->url([ 'user_id' => $self->id, 'query' => $self->username ], 'user')
							);

							//add activity
							\Activity\Activity::set((int) $user_info->id, 'LIKEPIN', (int) $row->pin_id);
						}
						////////
						$data['trues'] = true;
					} else {
						$data['error'] = 'error like';
					}
				}
				$pinLikeTable->getAdapter()->commit();
			} catch (\Core\Db\Exception $e) {
				$data['error'] = $e->getMessage();
				$pinLikeTable->getAdapter()->rollBack();
			}
			$data['info'] = \Pin\Pin::getInfo($request->getRequest('pin_id'));
			$data['infouser'] = \User\User::getInfo(\User\User::getUserData()->id);
		} else {
			$data['location'] = $this->url(array('controller' => 'login'),'user_c');
		}
		
		$this->responseJsonCallback( $data );
		
	}
	
}