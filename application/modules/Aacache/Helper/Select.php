<?php

namespace Aacache\Helper;

class Select {
	
	protected $column_data = array();
	
	public function __construct($column_data) {
		$this->column_data = $column_data;
		$this->column_data['type'] = null;
		$this->translate = new \Translate\Locale('Front\\'.__NAMESPACE__, \Core\Base\Action::getModule('Language')->getLanguageId());
	}
	
	public function form() {
		$this->column_data['list']['File'] = $this->translate->_('Flat file');
		if (extension_loaded('apc')) {
			$this->column_data['list']['Apc'] = $this->translate->_('Alternative PHP Cache (APC)');
		}
		if (extension_loaded('memcached')) {
			$this->column_data['list']['Libmemcached'] = $this->translate->_('libMemcached (http://libmemcached.org/)');
		}
		if (extension_loaded('memcache')) {
			$this->column_data['list']['Memcached'] = $this->translate->_('Memcached (http://memcached.org/)');
		}
		/*if (extension_loaded('sqlite')) {
			$this->column_data['list']['Sqlite'] = $this->translate->_('Sqlite database');
		}
		if (extension_loaded('wincache')) {
			$this->column_data['list']['WinCache'] = $this->translate->_('WinCache');
		}
		if (extension_loaded('xcache')) {
			$this->column_data['list']['Xcache'] = $this->translate->_('Xcache (http://xcache.lighttpd.net/)');
		}*/
		
		$this->column_data['type'] = 'Single';
		return $this->column_data;
	}
	
}