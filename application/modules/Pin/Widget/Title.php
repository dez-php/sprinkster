<?php

namespace Pin\Widget;

use Activity\Activity;

class Title extends \Core\Base\Widget {

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
	
	public function result() {
		$data['pin'] = $this->pin;
		$this->render('index', $data);
	}
	
	
}