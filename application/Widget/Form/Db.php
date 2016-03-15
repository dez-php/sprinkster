<?php

namespace Widget\Form;

class Db extends \Core\Base\Widget {

	protected $id;
	/**
	 * @var \Core\Db\Table\AbstractTable
	 */
	protected $dataProvider;
	protected $filter;
	protected $checkbox = true;
	protected $add_new = true;
	protected $columns;
	protected $columns_useds;
	protected $info;
	protected $referenceMap;
	protected $referenceMapHelp;
	protected $atributes;
	protected $head;
	
	protected $row_id;
	protected $errors = array();
	protected $errorsGlobal = array();
	
	protected $referenceRowNameAuto = array('title', 'name', 'username', 'email', 'firstname', 'description');


	const DEFAULT_VALUE_STORED = 'DEFAULT_VALUE_STORED';
	const ROW_AUTO_GET_NOT_FOUND = 'ROW_AUTO_GET_NOT_FOUND';
	
	public function init() {
		$this->_ = new \Translate\Locale(__NAMESPACE__, $this->getModule('Language')->getLanguageId());
	}

	public function preDispatch() {

		$request = $this->getRequest();
		if( !($this->dataProvider instanceof \Core\Db\Table\AbstractTable) ) {
			throw new \Core\Db\Exception('dataProvider must by instance of \Core\Db\Table\AbstractTable');
		}
		$this->info = $this->info ? $this->info : $this->dataProvider->info();
		
		if(!$this->id) {
			$this->id = $this->info['name'] . '-grid';
		}
		if(!$this->columns) {
			$this->setColumns($this->info['cols']);
		}
		
		if(isset($this->info['referenceMap']) && $this->info['referenceMap']) {
			foreach($this->info['referenceMap'] AS $rm) {
				if($rm['columns'] != $this->info['primary'][1]) {
					if(array_key_exists($rm['columns'], $this->columns_useds) !== false) {
						$this->referenceMap[strtolower($rm['refTableClass'])] = $rm['columns'];
					}
					$this->referenceMapHelp[strtolower($rm['refTableClass'])] = $rm;
				}
			}
		}
		
		$this->row_id = $request->getRequest('id');
		
	}
	
	public function setColumns($columns = array()) {
		$this->info = $this->info ? $this->info : $this->dataProvider->info();
		foreach($columns AS $row => $column) {
			if(isset($column['virtual']) && is_array($column['virtual']) && $column['virtual']) {
				list($key, $val) = each($column['virtual']);
				$this->columns_useds[$key] = $row;
				$this->columns[$key] = array(
						'name' => $key,
						'refTableClass' => $val,
						'refColumn' => $column['name'],
						'type' => '',
						'value' => '',
						'label' => isset($column['label'])&&$column['label']?$column['label']:\Core\Camel::toCamelCase($column['name'], true, true),
						'default' => '',
						'length' => '',
						'primary' => '',
						'required' => isset($column['required'])?$column['required']:false,
						'atributes' => isset($column['atributes'])?$column['atributes']:'',
						'callback' => isset($column['callback'])?$column['callback']:'',
						'validate' => isset($column['validate'])?$column['validate']:''
				);
			} else if(is_array($column) && isset($column['name']) && isset($this->info['metadata'][$column['name']])) {
				$column_name = $column['name'];
				$this->columns_useds[$column['name']] = $row;
				$this->columns[$column['name']] = array(
						'name' => $column['name'],
						'type' => isset($column['type'])&&$column['type']?$column['type']:$this->info['metadata'][$column['name']]['DATA_TYPE'],
						'value' => isset($column['value'])&&$column['value']?$column['value']:self::DEFAULT_VALUE_STORED,
						'label' => isset($column['label'])&&$column['label']?$column['label']:\Core\Camel::toCamelCase($column['name'], true, true),
						'default' => isset($column['default'])?$column['default']:$this->info['metadata'][$column['name']]['DEFAULT'],
						'length' => isset($column['length'])?$column['length']:$this->info['metadata'][$column['name']]['LENGTH'],
						'primary' => isset($column['primary'])?$column['primary']:$this->info['metadata'][$column['name']]['PRIMARY'],
						'required' => isset($column['required'])?$column['required']:true,
						'atributes' => isset($column['atributes'])?$column['atributes']:'',
						'callback' => isset($column['callback'])?$column['callback']:'',
						'validate' => isset($column['validate'])?$column['validate']:''
				);
			} else if(is_string($column) && isset($this->info['metadata'][$column])) {
				$this->columns_useds[$column] = $row;
				$this->columns[$column] = array(
					'name' => $column,
					'type' => $this->info['metadata'][$column]['DATA_TYPE'],
					'value' => self::DEFAULT_VALUE_STORED,
					'label' => \Core\Camel::toCamelCase($column, true, true),
					'default' => $this->info['metadata'][$column]['DEFAULT'],
					'length' => $this->info['metadata'][$column]['LENGTH'],
					'primary' => $this->info['metadata'][$column]['PRIMARY'],
					'required' => true,
					'atributes' => '',
					'callback' => '',
					'validate' => ''
				);
			}
		}
		return $this;
	}
	
	public function findColum(\Core\Db\Table\Row $data, $column = null) {
		$columns = $data->getTable()->getColumns();
		$search = null;
		foreach( $this->referenceRowNameAuto AS $name) {
			if( ($num = array_search($name, $columns)) !== false) {
				$search = $columns[$num];
				break;
			}
		}
		return $search&&isset($data[$search])?$data[$search]:self::ROW_AUTO_GET_NOT_FOUND;
	}
	
	
	/* (non-PHPdoc)
	 * @see \Core\Base\Widget::result()
	 */
	public function result() {
		$request = $this->getRequest();
		
		$data = array();
		
		$data['result'] = $this->dataProvider->fetchRow($this->dataProvider->makeWhere(array($this->info['primary'][1] => $this->row_id)));
		if(!$data['result'] || !$data['result']->{$this->info['primary'][1]}) { $data['result'] = $this->dataProvider->fetchNew(); }
		if($request->getPost($this->id)) {
			$form = $request->getPost('form_data');
			if($form) {
				foreach($form AS $k=>$v) {
					if(array_key_exists($k, $this->columns_useds)) {
						$data['result'][$k] = $v;
					}
				}
			}
			if($this->validate()) {
				$type = 'updated';
				if(!$data['result']->id) {
					$type = 'inserted';
					if(isset($data['result']->date_added)) {
						$data['result']->date_added = \Core\Date::getInstance(null,\Core\Date::SQL_FULL, true)->toString();
					}
					if(isset($data['result']->date_modified)) {
						$data['result']->date_modified = \Core\Date::getInstance(null,\Core\Date::SQL_FULL, true)->toString();
					}
				} else if(isset($data['result']->date_modified)) {
					$data['result']->date_modified = \Core\Date::getInstance(null,\Core\Date::SQL_FULL, true)->toString();
				}
				$demo_user_id = \Base\Config::get('demo_user_id');
				if($demo_user_id && $demo_user_id == \User\User::getUserData()->id) {
					$this->errorsGlobal['Exception'] = $this->_('You don\'t have permissions for this action!');
				} else {
					try {
						$data['result']->save();
						\Core\Session\Base::set($this->id . '-success', $this->_('Record successfully ' . $type));
						$this->redirect( $this->url(array('module' => $request->getModule()),'admin_module') );
					} catch (\Core\Exception $e) {
						$this->errorsGlobal['Exception'] = $e;
					}
				}
			}
		}
		
		$this->render('db', $data);
		
	}
	
	public function validate() {
		$request = $this->getRequest();
		$validator = new \Core\Form\Validator(array(
			'translate' => $this->_
		));
		for($i=0; $i<count($this->columns_useds);$i++) {
			$column = array_search($i, $this->columns_useds);
			$column_data = $this->columns[$column];
			if(is_callable($column_data['validate'])) {
				call_user_func($column_data['validate'], $column_data, $validator);
			} elseif($column_data['required']) {
				$valudator_data = array(
						'custom-value' => $request->getPost('form_data['.$column.']'),
						'min' => 1,
						'error_text_min' => $column_data['label'] . ' ' . $this->_('must contain no less than %d characters')
				);
				if($column_data['length']) {
					$valudator_data['max'] = $column_data['length'];
					$valudator_data['error_text_max'] = $column_data['label'] . ' ' . $this->_('must contain no more than %d characters');
				}
				$validator->addText($column, $valudator_data);
			}
		}
		if($validator->validate()) {
			return true;
		} else {
			$this->errors = $validator->getErrors();
			return false;
		}
	}
	
}