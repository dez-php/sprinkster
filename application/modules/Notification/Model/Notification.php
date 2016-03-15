<?php

namespace Notification;

class Notification extends \Base\Model\Reference {
	
	protected $_referenceMap    = array(
		'NotificationDescription' => array(
			'columns'           => 'id',
			'refTableClass'     => 'Notification\NotificationDescription',
			'refColumns'        => 'notification_id',
			'where'				=> '"language_id = " . \Core\Base\Action::getModule(\'Language\')->getLanguageId()'
		),
	);
	
	
	protected $replacers = array();
	
	protected $language_id;
	
	/**
	 * @param array $replacers
	 * @return \Notification\Notification
	 */
	public function setReplace($replacers = array()) {
		$this->replacers = $replacers;
		return $this;
	}

	/**
	 * @param number $language_id
	 * @return \Notification\Notification
	 */
	public function setLanguageId($language_id) {
		$this->language_id = $language_id;
		return $this;
	}

	public function getLanguageId() {
		$Language = \Core\Base\Action::getModule('Language');
		if($this->language_id && $Language->issetLanguage($this->language_id)) {
			return $this->language_id;
		} else {
			return $Language->getLanguageId();
		}
	}
	
	public function getReplace($footer = true) {
		if(!is_array($this->replacers)) {
			$this->replacers = array();
		}
		if(!isset($this->replacers['site_url']) || !$this->replacers['site_url']) {
			$this->replacers['site_url'] = \Core\Base\Action::getInstance()->url(array(),'welcome_home');
		}
		if(!isset($this->replacers['site_name']) || !$this->replacers['site_name']) {
			$this->replacers['site_name'] = \Meta\Meta::getGlobal('title');
		}
		/*if(!isset($this->replacers['mail_footer']) || !$this->replacers['mail_footer']) {
			$row = $footer ? $this->get('mail_footer') : false;
			$this->replacers['mail_footer'] = '';
			if($row) {
				$this->replacers['mail_footer'] = $row->description;
			}
		}*/
		return $this->replacers;
	}
	
	/**
	 * @param string $key
	 * @return multitype:
	 */
	public function get($key) {
		$sql = $this->getAdapter()->select()
					->from($this->_name)
					->joinLeft($this->_name . '_description', ''.$this->_name.'.id = '.$this->_name.'_description.notification_id', array('title','description'))
					->where($this->_name . '.`key` = ?', $key)
					->where($this->_name . '_description.language_id = ?', $this->getLanguageId());
		
		$rows = $this->getAdapter()->fetchRow($sql);
		
		if (!$rows) {
			return null;
		}
		if($key != 'mail_footer') {
			$replacers = $this->getReplace(false);
			if($replacers) {
				$search = $replace = array();
				foreach($replacers AS $s => $v) {
					$search[$s] = "~(?<=.\W|\W.|^\W)" . preg_quote('${'.$s.'}') . "(?=.\W|\W.|\W$)~";
					$replace[$s] = $v;
				}
				$rows['title'] = trim(preg_replace($search, $replace, ' ' . $rows['title'] . ' '));
				$rows['description'] = trim(preg_replace($search, $replace, ' ' . $rows['description'] . ' '));
			}
			$rows['description'] = \Core\Base\Action::getInstance()->render('template',array('title'=>$rows['title'], 'description'=>$rows['description'],'footer'=>$this->get('mail_footer'),'language_id' => $this->getLanguageId()),['module'=>'notification','controller'=>'email'],true);
		}
		
		$data = array(
				'table'   => $this,
				'data'     => $rows,
				'readOnly' => true,
				'stored'  => true
		);
		
		$rowClass = $this->getRowClass();
		if (!class_exists($rowClass)) {
			require_once 'Loader.php';
			\Core\Loader\Loader::loadClass($rowClass);
		}
		return new $rowClass($data);
	}
	
	public function send($group, $replace = []) {
		\Core\Http\Thread::run(
			[ '\Notification\Helper\Email', 'result' ],
			$group,
			$replace
		);
	}
	
}