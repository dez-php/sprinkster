<?php

namespace Admin\Widget;

class Menu extends \Base\Widget\AbstractMenu {
	
	protected $parent_id = null;
	protected static $selected_id = [];
	protected $group = 'AdminMenu';
	
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
	
	private function getActive() {
		if(!isset(self::$selected_id[$this->group])) {
			$self = new self();
			$max = 0;
			$id = 0; 
			$uri = explode('?',$self->getRequest()->getFullUri())[0];
			if($uri == 'admin')
				return self::$selected_id[$this->group] = 0;
			if(function_exists('similar_text')) {
				$all_menus = \Base\Menu::getMenu($this->group);
				foreach($all_menus AS $m) {
					if($m->widget) {
						$wroute = (array)unserialize($m->route);
						$route = 'admin_module';
						if(!isset($wroute['module'])) {
							$wroute['module'] = $m->widget;
						} else {
							$route = $m->widget;
						}
						$url = $self->url($wroute,$route);
						similar_text($url, $uri, $p);
						if($p > $max) {
							$max = $p;
							$id = $m->id;
						}
					}
				}
			} 
			self::$selected_id[$this->group] = $max > 40 ? $id : 0;
		}
		return self::$selected_id[$this->group];
	}
	
	/* (non-PHPdoc)
	 * @see \Core\Base\Widget::result()
	 */
	public function result() {
		
		// get categories for menu
		/*$categoryTable = new \Category\Category();
		$data['categories'] = $categoryTable->getAllIdTitle($this->parent_id);
		
		if($this->getParent_Id()) {
			$this->render('menuChild', $data);
		} else {
			$this->render('menu', $data);
		}*/
		$data['menu'] = \Base\Menu::getMenu($this->group, $this->getParent_Id());
		
		$data['active'] = $this->getActive();
		
		if($this->getParent_Id()) {
			$this->render('menuChild', $data);
		} else {
			$this->render('menu', $data);
		}
	}
	
}