<?php

namespace Base;

class MenuRow extends \Core\Db\Table\Row {
	
	public $hack = 0;
	
    /**
     * Allows pre-insert logic to be applied to row.
     * Subclasses may override this method.
     *
     * @return void
     */
    protected function _insert() {
    	if(!$this->guid) { $this->guid = \Core\Base\Core::guid( (int)$this->parent_id . $this->title . $this->group_id ); }
    }

    /**
     * Allows pre-update logic to be applied to row.
     * Subclasses may override this method.
     *
     * @return void
     */
    protected function _update() {
    	if(!$this->guid) { $this->guid = \Core\Base\Core::guid( (int)$this->parent_id . $this->title . $this->group_id ); }
    }
    
    public function disabled($data = null, $config = null) {
        $key_cache = md5(serialize([$this->toArray(), $data, $config]));


    	if($this->issetCache($key_cache))
    		return $this->getCache($key_cache);

    	static $action = null;
    	if($action === null)
    		$action = \Core\Base\Action::getInstance();

    	$sys_check = false;
    	$modules = [];
    	if(trim($this->has_required) && count($modules = explode(',', $this->has_required)) > 0) {
    		$sys_check = true;
    		foreach($modules AS $module) {
    			if($action->isModuleAccessible($module)) {
    				$sys_check = false;
    				break;
				}
			}
		}
    	if($this->disabled && trim($this->disabled)) {
    		try {
    			if($modules)
    				return $this->setCache($key_cache, $sys_check);
    			return $this->setCache($key_cache, @eval($this->disabled));
			} catch (\Core\Exception $e) {
    			return $this->setCache($key_cache, $sys_check);
			}
		} else {
    		return $this->setCache($key_cache, $sys_check);
		}
    	
    	return $this->setCache($key_cache, $sys_check);
    }
	
    private function getCache($key) {
    	if($this->issetCache($key))
    		return \Core\Registry::get(__NAMESPACE__ . '_menu_' . $key);
    }
	
    private function issetCache($key) {
    	return \Core\Registry::isRegistered(__NAMESPACE__ . '_menu_' . $key);
    }
	
    private function setCache($key, $value) {
    	\Core\Registry::set(__NAMESPACE__ . '_menu_' . $key, $value);
    	return $value;
    }
    
}