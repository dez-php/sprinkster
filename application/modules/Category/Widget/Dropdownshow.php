<?php

namespace Category\Widget;

use \Core\Interfaces\IMemcachedWidget;

class Dropdownshow extends \Base\Widget\AbstractMenuPermissionWidget {

	const Cols = 3;
	const Depth = 2;

	private $cols;
	private $depth;
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function setCols($cols) {
		$this->cols = (int)$cols;
		return $this;
	}

	public function setDepth($depth) {
		$this->depth = (int)$depth;
	}

	public function getCols() {
		return $this->cols ? : self::Cols;
	}

	public function getDepth() {
		return $this->depth ? : self::Depth;
	}
	
	public function result()
	{
		$categories = \Category\Category::getTree(NULL, $this->getDepth());

		$containers = [];
		for($i = 0; $i < $this->getCols(); $i++)
			$containers[$i] = [];

		if($categories && 0 < count($categories))
		foreach($categories as $index => $category)
			$containers[$index % $this->getCols()][] = $category;

		$this->render('dropdownshow', [ 'containers' => $containers ]);
	}

}