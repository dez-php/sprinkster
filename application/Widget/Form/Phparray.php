<?php

namespace Widget\Form;

class Phparray extends \Core\Base\Widget {

	protected $id;
	
	public $record_id;
	protected $columns;
	protected $tabs = array();
// 	protected $onSave;
	protected $onCancel;
	protected $validator = null;
	protected $head;
	protected $form;
	protected $errors = null;
	protected $description;
	protected $descriptionValue;
	protected $helpText;
	
	public function setId($id) {
		$this->id = $id;
		return $this;
	}
	
	public function init() {
		$request = $this->getRequest();
		$this->_ = new \Translate\Locale('Backend\\'.$this->getFrontController()->formatModuleName($request->getModule()), $this->getModule('Language')->getLanguageId());
	}

	public function preDispatch() {
		$request = $this->getRequest();
		if(!$this->id) {
			$this->id = $request->getModule() . '_form';
		}
	}
	
	private function formatColumns($columns = array()) {
		$return = array();
		foreach($columns AS $row => $column) {
			$return[$column['name']] = array(
				'name' => $column['name'],
				'type' => isset($column['type'])&&$column['type']?$column['type']:null,
				'value' => isset($column['value'])&&$column['value']?$column['value']:'',
				'list' => isset($column['list'])&&$column['list']?$column['list']:'',
				'label' => isset($column['label'])&&$column['label']?$column['label']:\Core\Camel::toCamelCase($column['name'], true, true),
				'required' => isset($column['required'])?$column['required']:false,
				'readonly' => isset($column['readonly'])?$column['readonly']:null,
				'help' => isset($column['help'])?$column['help']:false,
				'autocomplete' => isset($column['autocomplete'])?$column['autocomplete']:null,
				'options' => isset($column['options'])&& is_array($column['options'])?$column['options']:array(),
// 				'min' => isset($column['min'])?$column['min']:null,
// 				'max' => isset($column['max'])?$column['max']:null,
// 				'step' => isset($column['step'])?$column['step']:null
			);
		}
		return $return;
	}
	
	public function setOnSave($onSave) { 
		$this->extend('onSave', $onSave);
		return $this;
	}
	
	public function setDescription($columns = array()) {
		$this->description = $this->formatColumns($columns);
		return $this;
	}
	
	public function setColumns($columns = array()) {
		$this->columns = $this->formatColumns($columns);
		return $this;
	}
	
	public function result() {
		
		$request = $this->getRequest();

		$data = array();
		if(\Core\Session\Base::get($this->id . '-success')) {
			$data['success'] = \Core\Session\Base::get($this->id . '-success');
			\Core\Session\Base::clear($this->id . '-success');
		} else if(\Core\Session\Base::get($this->id . '-error')) {
			$data['error'] = \Core\Session\Base::get($this->id . '-error');
			\Core\Session\Base::clear($this->id . '-error');
		}
		
		$this->render('phparray', $data);
	}
	
	
}