<?php

namespace Invite;

class AdminController extends \Core\Base\Action {
	
	public function init() {
		if(!\User\User::getUserData()->is_admin) { $this->redirect($this->url([],'admin_login')); }
		$this->_ = new \Translate\Locale('Backend\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() {
		$this->render('index');
	}
	
	public function sendAction() {
		$request = $this->getRequest();
		$id = $request->getQuery('id');
		$form = $request->getQuery('form');
		$inviteTable = new \Invite\Invite();
		$invite = $inviteTable->fetchRow(array('id = ?' => $id));
		if($invite) {
			$NotificationTable = new \Notification\Notification();
			$Notification = $NotificationTable->setReplace(array(
					'invate_url' => $this->url(array('controller' => 'register', 'query' => '?invited_code=' . $invite->code),'user_c', false, false)
			))->get('send_invite_admin');
			if($Notification) {
				$email = new \Helper\Email();
				$email->addFrom(\Base\Config::get('no_reply'));
				$email->addTo($invite->email);
				$email->addTitle($Notification->title);
				$email->addHtml($Notification->description);
				if($email->send()) {
					$invite->send = 1;
					try {
						$invite->save();
						\Core\Session\Base::set($form.'-success', $this->_('The invitation was sent successfully!'));
						$this->redirect($request->getServer('HTTP_REFERER'));
					} catch (\Core\Exception $e) {
						\Core\Session\Base::set($form.'-error', $e->getMessage());
						$this->redirect($request->getServer('HTTP_REFERER'));
					}
				} else {
					\Core\Session\Base::set($form.'-error', $email->ErrorInfo);
					$this->redirect($request->getServer('HTTP_REFERER'));
				}
			}
		} else {
			\Core\Session\Base::set($form.'-error', $this->_('Record not found!'));
			$this->redirect($request->getServer('HTTP_REFERER'));
		}
	}
	
	public function totalAction() {		
		$userTable = new \Invite\Invite();
		$total = $userTable->countBy(array('user_id'=>null,'send'=>'0'));
		$userTable->getAdapter()->query("DELETE FROM `statistics` WHERE `type` = 4");
		$userTable->getAdapter()->query("INSERT INTO `statistics`(`id`, `total`, `type`) SELECT DATE_FORMAT(`date_added`, '%Y%m'),COUNT(id),4 FROM invite WHERE user_id IS NULL AND send=0 GROUP BY DATE_FORMAT(`date_added`, '%Y%m');");
		$this->responseJsonCallback(array('total' => $total));
	}
	
}