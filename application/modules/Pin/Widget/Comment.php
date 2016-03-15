<?php

namespace Pin\Widget;

use Activity\Activity;

class Comment extends \Core\Base\Widget {

	protected $pin;
	protected $page_size = NULL;

	const DefaultPageSize = 5;
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	/**
	 * @param \Core\Db\Table\Row\AbstractRow $pin
	 * @return \Pin\Widget\Comment
	 */
	public function setPin($pin) {
		$this->pin = $pin;
		return $this;
	}
	
	/**
	 * @return \Core\Db\Table\Row\AbstractRow
	 */
	public function getPin() {
		return $this->pin;
	}
	
	protected function deleteCommentView($comment_id) {
		$commentTable = new \Pin\PinComment();
		$comment_info = $commentTable->fetchRow(array('id = ?' => (int)$comment_id));
		$request = $this->getRequest();
		if(!$comment_info) {
			$request->setQuery('widget', '');
			$this->forward('error404');
		}
		
		$data = [
			'comment_id' => $comment_id,
			'isXmlHttpRequest' => $request->isXmlHttpRequest() ? '-popup' : ''
		];
		
		$this->render('delete', $data);
	}
	
	protected function deleteComment($comment_id) {
		$self = \User\User::getUserData();
		$result = array();
		if($self->id) {
			$commentTable = new \Pin\PinComment();
			$comment = $commentTable->fetchRow(array('id = ?' => $comment_id));
			if($comment) {
				$pinTable = new \Pin\Pin();
				$pin_data = $pinTable->fetchRow(array('id = ?' => $comment->pin_id));
				if($pin_data) {
					$comuser = $comment->user_id;
					if($self->id == $comment->user_id || $self->id == $pin_data->user_id || $self->is_admin) {
						if($comment->delete()) {
							$result['deleted'] = true;
							$userTable = new \User\User();
							$user = $userTable->fetchRow(array('id = ?' => $comuser));
							if($user) {
								$user->comments = $commentTable->countByUserId($comuser);
								$user->save();
							}
						} else {
							$result['error'] = $this->_('There was a problem with the record. Please try again!');
						}
					} else {
						$result['error'] = $this->_('You don\'t have permission to delete the comment');
					}
				} else {
					$result['error'] = $this->_('There was a problem with the record. Please try again!');
				}
			} else {
				$result['error'] = $this->_('There was a problem with the record. Please try again!');
			}
		} else {
			$result['location'] = $this->url(array('controller' => 'login'),'user_c');
		}
		return $result;
	}
	
	protected function reportComment($comment_id) {
		$request = $this->getRequest();
		$data = array();
		
		$self_data = \User\User::getUserData();
		
		$commentTable = new \Pin\PinComment();
		$comment_info = $commentTable->fetchRow(array('id = ?' => $comment_id));
		
		if($request->isPost()) {
			if(!$self_data->id) {
				$return['location'] = $this->url(array('controller' => 'login'),'user_c');
			} else {
				$report_category = $request->getPost('report_category');
				$validator = new \Core\Form\Validator(array(
						'translate' => $this->_
				));
				if($request->getPost('report_category') == -1) {
					$report_category = null;
					$validator->addText('report_message', array(
							'min' => 3,
							'error_text_min' => $this->_('Message must contain more than %d characters')
					));
				}
				if($validator->validate()) {
					$pinReport = new \Pin\PinCommentReport();
					$new = $pinReport->fetchNew();
					$new->comment_report_category_id = $report_category;
					$new->user_id = $self_data->id;
					$new->date_added = \Core\Date::getInstance(null,\Core\Date::SQL_FULL, true)->toString();
					$new->comment_id = $comment_id;
					$new->message = $request->getPost('report_message');
					try {
						$new->save();
						
						////notify
						$email = new \Helper\Email();
						$email->addFrom(\Base\Config::get('no_reply'));
						$email->addTo(\Base\Config::get('site_owner_reply'));
						$email->addTitle($this->_('New reported comment'));
						$email->addHtml(sprintf($this->_('Hello, there is new reported comment in %s'), \Meta\Meta::getGlobal('title')));
						$email->send();
						/////////
						
						$return['reported'] = true;
					} catch (\Core\Exception $e) {
						$return['errors']['Exception'] = $e->getMessage();
					}
				} else {
					$return['errors'] = $validator->getErrors();
				}
			}
			$this->responseJsonCallback($return);
			exit;
		}
		
		if(!$request->isXmlHttpRequest()) {
			$this->redirect($this->url(array('controller' => 'login'),'user_c'));
		} 
		if(!$comment_info) {
			$request->setQuery('widget', '');
			$this->forward('error404');
		}
		
		$categoryTable = new \Pin\PinCommentReportCategory();
		$data['categories'] = $categoryTable->getAll();
		$data['comment'] = $comment_info;
		
		$this->render('report', $data);
	}
	
	public function addComment() {
		///add comment
		$request = $this->getRequest();
		$pin_id = $request->getPost('pin_id');
		$friends = $request->getPost('friends');
		$comment = $request->getPost('comment');
		$isXmlHttpRequest = $request->getPost('isXmlHttpRequest') == 'true';
		$search = $replace = $notify_friends = array();
		if($friends) {
			$comment = ' ' . $comment . ' ';
			foreach($friends AS $id => $name) {
				$search[$id] = "~(?<=.\W|\W.|^\W)" . preg_quote($name) . "(?=.\W|\W.|\W$)~";
				$replace[$id] = ' @[[['.$id.':'.$name.']]] ';
				$notify_friends[$id] = $id;
			}
			$comment = trim(preg_replace($search, $replace, $comment));
		} 
		$self_data = \User\User::getUserData();
		$return=array();
		if($self_data->id) {
			$commentTable = new \Pin\PinComment();
			$new = $commentTable->fetchNew();
			$new->pin_id = $pin_id;
			$new->user_id = $self_data->id;
			$new->comment = htmlspecialchars($comment, ENT_QUOTES, 'utf-8');
			$new->date_added = \Core\Date::getInstance(null,\Core\Date::SQL_FULL, true)->toString();
			
			$validator = new \Core\Form\Validator(array(
				'translate' => $this->_
			));

			$validator->addText('comment', array(
				'custom-value' => trim($new->comment),
				'min' => 3,
				'error_text_min' => $this->_('Comment must contain more than %d characters')
			));
			
			if($validator->validate()) {
				$commentTable->getAdapter()->beginTransaction();
				try {
					$new->save();
					$self_data->comments = $commentTable->countByUserId($self_data->id);
					$self_data->save();
					$pinTable = new \Pin\Pin();
					$pin = $pinTable->fetchRow(array('id = ?' => $pin_id));
					$pin->comments = $commentTable->countByPinId($pin_id);
					$pin->save();
					////////////////////////send notification
					$userTable = new \User\User();
					$pinTable = new \Pin\Pin();
					$user_info = $userTable->fetchRow($userTable->makeWhere(array('id'=>array($pinTable->select()->from($pinTable,'user_id')->where('id = ?',$pin_id)))));
					if($user_info && $self_data->id != $user_info->id) {
						if($user_info->notification_comment_pin) {
							//send notifikation
							$NotificationTable = new \Notification\Notification();
							$NotificationTable->send('comment_pin', [
								'user_id' => $user_info->id,
								'user_firstname' => $user_info->firstname,
								'user_lastname' => $user_info->lastname,
								'user_username' => $user_info->username,
								'user_fullname' => $user_info->getUserFullname(),
								'pin_url' => $this->url(array('pin_id'=>$pin_id),'pin'),
								'author_url' => $this->url(array('user_id'=>$self_data->id,'query'=>$self_data->username),'user'),
								'author_fullname' => $self_data->getUserFullname(),
									
								'language_id' => $user_info->language_id,
								'email' => $user_info->email,
								'fullname' => $user_info->getUserFullname(),
								'notify' => $user_info->notification_comment_pin
							]);
							
						}
						//add activity
						\Activity\Activity::set($user_info->id, 'COMMENTPIN',$pin_id, null, html_entity_decode($request->getPost('comment'), ENT_QUOTES, 'utf-8'));
					}
					//////// notify_friends
					if($notify_friends) {
						$NotificationTable = new \Notification\Notification();
						$users = $userTable->fetchAll($userTable->makeWhere(array('id' => $notify_friends)));
						foreach($users AS $user) {
							if($user->notification_mentioned) {
								//send notifikation
								$NotificationTable = new \Notification\Notification();
								$NotificationTable->send('comment_mentioned', [
									'user_id' => $user_info->id,
									'user_firstname' => $user->firstname,
									'user_lastname' => $user->lastname,
									'user_username' => $user->username,
									'user_fullname' => $user->getUserFullname(),
									'pin_url' => $this->url(array('pin_id'=>$pin_id),'pin'),
									'author_url' => $this->url(array('user_id'=>$self_data->id,'query'=>$self_data->username),'user'),
									'author_fullname' => $self_data->getUserFullname(),
											
									'language_id' => $user->language_id,
									'email' => $user->email,
									'fullname' => $user->getUserFullname(),
									'notify' => $user->notification_mentioned
								]);
								
							}
							//add activity
							Activity::set($user->id, 'MENTIONED',$pin_id, null, html_entity_decode($request->getPost('comment'), ENT_QUOTES, 'utf-8'));
						}
					}
					////////////////////////////////////
					
					$return['html'] = $this->render('commentPost', [
						'comment' => $commentTable->get($new->id),
						'isXmlHttpRequest' => $isXmlHttpRequest	? '-popup' : ''
					], TRUE);
					$return['infouser'] = \User\User::getInfo($self_data->id);
					$return['info'] = \Pin\Pin::getInfo($pin_id);
					$commentTable->getAdapter()->commit();
				} catch (\Core\Exception $e) {
					$commentTable->getAdapter()->rollBack();
					$return['errors']['Exception'] = $e->getMessage();
				}
			} else {
				$return['errors'] = $validator->getErrors();
			}
		} else {
			$return['errors']['session'] = $this->_('Your login session has expired.');
		}
		
		return $return;
	}
	
	protected function mentionsInput($text) {
		//$text = \Pin\Helper\Description::tag($text);
		static $search = array(), $replace = array();
		
		$text = html_entity_decode($text, ENT_QUOTES, 'utf-8');
		if( preg_match_all('~\#([^ ]*)~i', $text, $matches)) {
			$text = htmlspecialchars ( $text, ENT_QUOTES, 'utf-8' );
			$s = $r = array();
			foreach($matches[0] AS $k => $match) {
				$s[$match] = "~(?<=.\W|\W.|^\W)" . preg_quote($match) . "(?=.\W|\W.|\W$)~";
				$r[$match] = $this->render('link2', array(
						'url' => $this->url(array('query'=>$matches[1][$k]),'search_pin'),
						'title' => $matches[1][$k]
					), true);
			}
			if($s) {
				$text = trim(preg_replace($s, $r, ' ' . $text . ' '));
			}
		} else {
			$text = htmlspecialchars ( $text, ENT_QUOTES, 'utf-8' );
		}
		
		
		if(preg_match_all('~\@\[\[\[(?<user_id>[\d]{1,}):(?<username>[^\]]*)\]\]\]~', $text, $matches, PREG_SET_ORDER)) {
			$users = array();
			foreach($matches AS $match) {
				if(!isset($replace[$match['user_id']])) {
					$search[$match['user_id']] = "~(?<=.\W|\W.|^\W)" . preg_quote($match[0]) . "(?=.\W|\W.|\W$)~";
					$users[$match['user_id']] = $match['user_id'];
					$replace[$match['user_id']] = $match['username'];
				}
			}
			
			if($users) {
				$userTable = new \User\User();
				$users = $userTable->fetchAll($userTable->makeWhere(array('id' => $users)));
				foreach($users AS $user) {
					$replace[$user->id] = $this->render('link', array(
						'url' => $this->url(array('user_id' => $user->id,'query' => $user->username),'user'),
						'title' => $user->getUserFullname()
					), true);
				}
			}
		
			if($replace) {
				$text = trim(preg_replace($search, $replace, ' ' . $text . ' '));
			}
			
		}
		return $text;
	}
	
	public function result() {
		$request = $this->getRequest();
		
		if($request->getRequest('delete') && $request->isPost())
		{
			$this->responseJsonCallback($this->deleteComment($request->getRequest('delete')));
			exit;
		}

		if($request->getRequest('report'))
			return $this->reportComment($request->getRequest('report'));

		if($request->getRequest('delete') && $request->isGet())
			return $this->deleteCommentView($request->getRequest('delete'));

		if($request->issetRequest('addComment'))
			return $this->responseJsonCallback($this->addComment());
		
		$data = [
			'comments' => [],
			'isXmlHttpRequest' => $request->isXmlHttpRequest() ? '-popup' : ''
		];
		
		/*if($this->pin && $this->pin->id) {
			$commentTable = new \Pin\PinComment();
			$data['comments'] = $commentTable->getAll($this->pin->id, 'id DESC');
		}*/
		
		$data['from_widget'] = $request->getRequest('widget');
		$data['from_widget']=true;
		$this->options['pin_id'] = $this->pin ? $this->pin->id : (isset($this->options['pin_id'])?$this->options['pin_id']:0);
		/*if(!$data['from_widget'] && $this->pin && $this->pin->id)
		{
			$this->options = array('pin_id' => $this->pin->id);
			$data['query'] = 'options='.urlencode(serialize($this->options));
			$this->render('comment', $data);
		}
		else*/if(isset($this->options['pin_id']))
		{
			$commentTable = new \Pin\PinComment();
			$pinTable = new \Pin\Pin();

			if(0 >= $this->page_size)
				$this->page_size = (int) self::DefaultPageSize;

			$page = (int) $request->getRequest('page');
			$pages = ceil($commentTable->countByPinId_Status((int) $this->options['pin_id'], 1) / $this->page_size);

			if(0 >= $page)
				$page = 1;

			if($pages < $page)
				$page = $pages;

			if(0 >= $page)
				$page = 1;

			$limit = 0 < $page ? (int) $this->page_size : NULL;
			$offset = 0 < $page ? ($page - 1) * (int) $this->page_size : NULL;

			$paging = (object) [
				'page' => $page,
				'pages' => $pages,
				'page_size' => $this->page_size,
			];

			$data['paging'] = $paging;
			$data['query'] = 'options='.urlencode(serialize($this->options));

			$request = false;
			if(!$this->pin) {
				$this->pin = $pinTable->fetchRow($pinTable->select()->where($pinTable->makeWhere(array(
						'id' => $this->options['pin_id'],
						'status' => 1
					)))->useIndex(array('PRIMARY','status')));
				$request = true;
			}

			$data['comments'] = $commentTable->getAll($this->options['pin_id'], 'id ASC', $limit, $offset);
			if($request) {
				$this->responseJsonCallback($this->render('comment', $data, true));
			} else {
				$this->render('comment', $data);
			}
		}
		
		//$this->render('comment', $data);
	}
	
	
}