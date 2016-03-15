<?php
namespace Base;

use \Core\Base\MemcachedManager;

class Config extends \Base\Model\Reference {
	
	public static $errors;

	/**
	 * @var \Base\Config
	 */
	public static $instance = null;

    /**
     * @var \Core\Registry
     */
    protected $_registry = null;

	public static function getInstance($config = array()) {
		if(is_null(self::$instance)) {
			self::$instance = new self($config);
			self::$instance->_registry = \Core\Registry::getInstance();
			$rows = self::$instance->fetchAll();
			foreach($rows AS $row) {
				if($row->serialize) {
					self::$instance->_registry->set($row->key, unserialize($row->value));
				} else {
					self::$instance->_registry->set($row->key, $row->value === '0' ? 0 : $row->value);
				}
			}
		}
		return self::$instance;
	}
	
	public static function getRegExp($regexp) {
		$data = self::getInstance();
		return $data->_registry->getRegExp($regexp);
	}
	
	public static function get($key) {
		$data = self::getInstance();
		$cache_key = MemcachedManager::key(__CLASS__, __METHOD__, $key);

		//return MemcachedManager::get($cache_key, function() use ($data, $key) {
			//var_dump($data->_registry->isRegistered($key) ? $data->_registry->get($key) : NULL);
			return $data->_registry->isRegistered($key) ? $data->_registry->get($key) : NULL;
		//});
	}
	
	public static function set($key, $value = null) {
		$data = self::getInstance();
		$data->_registry->set($key, $value);
		return self::$instance;
	}
	
	public static function getGroupForm($group, $translate = null) {
		$self = new self();
		$data = $self->fetchAll($self->makeWhere(array('group' => $group)), 'sort_order ASC');
		$return = array();
		foreach($data AS $row) {
			if(strtolower($row->form_type) == 'html') {
				$row->key = str_replace('{local}', \Core\Http\Request::getInstance()->getBaseUrl(), $row->value);
			}
			$form_list = unserialize($row->form_list);
			if($form_list && is_array($form_list) && $translate instanceof \Core\Locale\Translate) {
				foreach($form_list AS $key => &$value) {
					$value = $translate->_($value);
				}
			}
			
			$return[$row->key] = array(
				'name' => $row->key,
				'type' => $row->form_type,
				'value' => $row->serialize ? unserialize($row->value) : $row->value,
				'list' => $row->form_list ? unserialize($row->form_list) : false,
				'label' => $translate instanceof \Core\Locale\Translate ? $translate->_($row->form_label) : $row->form_label,
				'required' => $row->form_required ? true : false,
				'help' => $row->form_helpMessage
			);
		}
		return $return;
	}
	
	public static function updateGroup($group, $data) {
		$self = new self();
		$self->getAdapter()->beginTransaction();
		try {
			$rows = $self->fetchAll($self->makeWhere(array('group' => $group,'key' => array_keys($data))));
			foreach($rows AS $row) {
				if(isset($data[$row->key])) {
					$row->serialize = (int)is_array($data[$row->key]);
					$row->value = $row->serialize ? serialize($data[$row->key]) : $data[$row->key];
					$row->save();
				}
			}
			$self->getAdapter()->commit();
			return true;
		} catch (\Core\Exception $e) {
			$self->getAdapter()->rollBack();
			$self::$errors = $e->getMessage();
			return false;
		}
	}
	
	public static function updateKey($key, $data) {
		$self = new self();
		try {
			return $self->update([
				'serialize' => (int)is_array($data),
				'value' => is_array($data) ? serialize($data) : $data
			], ['key = ?' => $key]);
		} catch (\Core\Exception $e) {
			$self::$errors = $e->getMessage();
			return false;
		}
	}
	
	public static function getErrors() {
		return self::$errors;
	}
	
	/**
	 * @param string $thumbs
	 * @return \Local\Helper\AbstractUpload
	 */
	public static function getUploadMethod($module = null, $thumbs = null) {
		$method = self::get('config_upload_method');
		
		if(!$method || !\Core\Base\Action::getInstance()->isModuleAccessible($method)) {
			$method = 'Local';
		}
		
		$method = \Core\Base\Front::getInstance()->formatHelperName('\\'.$method . '\Helper\Upload');
		return new $method($module, $thumbs);
	}
	
}