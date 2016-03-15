<?php

namespace Category\Widget;

class Menu extends \Base\Widget\AbstractMenuPermissionWidget {
	
	protected $columns = 3;
	
	protected $parent_id = null;
	
	/* (non-PHPdoc)
	 * @see \Core\Base\Action::init()
	 */
	public function init() {
		$this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	/**
	 * @param null|number $parent_id
	 * @return \Category\Widget\Menu
	 */
	public function setParent_Id($parent_id) {
		$this->parent_id = $parent_id;
		return $this;
	}
	
	public function getParent_Id() {
		return $this->parent_id;
	}

	/**
	 * @param number $columns
	 * @return \Category\Menu
	 */
	public function setColumns($columns) {
		if((int)$columns > 0) {
			$this->columns = (int)$columns;
		}
		return $this;
	}

	/**
	 * @return number
	 */
	public function getColumns() {
		return $this->columns;
	}
	
	/* (non-PHPdoc)
	 * @see \Core\Base\Widget::result()
	 */
	public function result() {
		
		// get categories for menu
		$categoryTable = new \Category\Category();
		$data['categories'] = $categoryTable->getAllIdTitle($this->parent_id);
		
		if($this->getParent_Id()) {
			$this->render('menuChild', $data);
		} else {
			$this->render('menu', $data);
		}
	}
	
}