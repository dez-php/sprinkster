<?php

namespace Widget\Grid;

class Db3 extends \Core\Base\Widget {

	protected $id;
	/**
	 * @var \Core\Db\Table\AbstractTable
	 */
	protected $dataProvider;
	protected $filter;
	protected $checkbox = true;
	protected $add_new = true;
	protected $add_url = null;
	protected $columns;
	protected $columns_useds;
	protected $info;
	protected $referenceMap;
	protected $referenceMapHelp;
	protected $atributes;
	protected $nosearch = false;
	protected $nopagination = false;
	protected $sql;
	
	protected $referenceRowNameAuto = array('title', 'name', 'username', 'email', 'firstname', 'description', 'comment');
	
	protected $limit = 10;
	protected $order;
	protected $groupBy;
	protected $sort;
	protected $page = 1;
	protected $has_edit = true;
	protected $has_delete = true;
	protected $callToAction = false;
	protected $has_delete_button = true;
	protected $head;
	protected $onDelete;
	protected $replaceDelete;
	protected $replaceEditLink;
	protected $actionsExtend;
	protected $buttonsExtend;
	protected $onPost;
	protected $orderExpr;
	protected $hasForm = true;

	protected $editInNewWindow = true;

	const DEFAULT_VALUE_STORED = 'DEFAULT_VALUE_STORED';
	const ROW_AUTO_GET_NOT_FOUND = 'ROW_AUTO_GET_NOT_FOUND';
	
	public function init() {
		$perPage = (int) \Base\Config::get('results_per_db3_grid');
		if($perPage) {
			$this->limit = (int) \Base\Config::get('results_per_db3_grid');
		}
		$this->_ = new \Translate\Locale(__NAMESPACE__, $this->getModule('Language')->getLanguageId());
	}
	
	public function setOrder($order) {
		$this->order = $order;
		return $this;
	}

	public function setGroupBy($groupBy) {
		$this->groupBy = $groupBy;
		return $this;
	}
	
	public function setSort($sort) {
		$this->sort = $sort;
		return $this;
	}
	
	public function setFilter($filter) {
		$this->filterExtends = $filter;
		return $this;
	}

	public function preDispatch() {

		$request = $this->getRequest();
		if( !($this->dataProvider instanceof \Core\Db\Table\AbstractTable) ) {
			throw new \Core\Db\Exception('dataProvider must by instance of \Core\Db\Table\AbstractTable');
		}
		$this->info = $this->info ? $this->info : $this->dataProvider->info();
		$columns = $this->dataProvider->info('cols');
		if(!$this->id) {
			$this->id = $this->info['name'] . '-grid';
			$form = new \Core\Htmlform\Htmlform($this->info['name'] . '_form');
			$form->clearSession();
		}
		if(!$this->columns) {
			$this->setColumns($this->info['cols']);
		}
		if($request->getQuery('order') && strtolower($request->getQuery('order')) == 'asc') {
			$this->order = 'ASC';
		} else if( in_array(strtolower($this->order), array('asc','desc')) ) {
			
		} else {
			$this->order = 'DESC';
		}
		if($request->getQuery('sort') && array_search($request->getQuery('sort'), $columns) !== false) {
			$this->sort = $request->getQuery('sort');
		} else if($this->sort && (array_search($this->sort, $columns) !== false || $this->sort instanceof \Core\Db\Expr)) {
			
		} else {
			$this->sort = $this->info['primary'][1];
		}
		
		if(isset($this->info['referenceMap']) && $this->info['referenceMap']) {
			foreach($this->info['referenceMap'] AS $rm) {
				if($rm['columns'] != $this->info['primary'][1]) {
					foreach($this->columns_useds AS $row => $col) {
						if($col['name'] == $rm['columns']) {
							$this->referenceMap[$rm['refTableClass']] = $rm['columns'];
						}
					}
					/*if(array_key_exists($rm['columns'], $this->columns_useds) !== false) {
						$this->referenceMap[$rm['refTableClass']] = $rm['columns'];
					}*/
					$this->referenceMapHelp[$rm['refTableClass']] = $rm;
				}
			}
		}
		//$fnc = create_function('', 'return '.$this->referenceMapHelp['category\category']['referenceMap']['where'].';');
		//var_dump( $fnc() ); exit;
		$this->page = (int)$request->getQuery('page') > 0 ? (int)$request->getQuery('page') : 1;

		if( is_array($filters = $request->getQuery('filter')) ) {
			$makeFilter = array();
			foreach($filters AS $filter => $value) {
				if( in_array($filter, $this->info['cols']) && $value !== '' && $value !== '*' && !is_null($value) ) {
					$rowType = $this->info['metadata'][$filter]['DATA_TYPE'];
					if(in_array($rowType, array('text','varchar', 'char'))) {
						$wordsAll = new \Core\Text\ParseWords($value);
						$words = $wordsAll->getMinLenght(2);
						$temp = array();
						foreach($words AS $word) {
							$temp[$word] = $filter . ' LIKE \'%' . $word . '%\'';
						}
						if($temp) {
							$makeFilter['where'][$filter] = '('.implode(' OR ', $temp).')';
						}
					} elseif(in_array($rowType, array('int','tinyint', 'bigint'))) {
						if($value == 'null') {
							$makeFilter[$filter] = null;
						} else {
							$makeFilter[$filter] = (int)$value;
						}
					} elseif(in_array($rowType, array('date','datetime'))) {
						$makeFilter['where'][$filter] = 'DATE(' . $filter . ') = \'' . \Core\Date::getInstance($value,'yy-mm-dd', true)->toString() . '\'';
					} elseif(strtolower($value) == 'null') {
						$makeFilter[$filter] = NULL;
					} else {
						$makeFilter[$filter] = $value;
					}
				}
			}
			$this->filter = $this->dataProvider->makeWhere($makeFilter);
		} 
		if($this->filterExtends) {
			$this->filter .= $this->filter? ' AND ' : '';
			$this->filter .= $this->dataProvider->makeWhere($this->filterExtends);
		}
		
	}
	
	public function setColumns($columns = array()) {
		$this->info = $this->info ? $this->info : $this->dataProvider->info();
		foreach($columns AS $row => $column) {
			if(isset($column['virtual']) && is_array($column['virtual']) && $column['virtual']) {
				list($key, $val) = each($column['virtual']);
				$this->columns_useds[$row] = array(
					'name' => 'virtual_'.$key,
					'row' => $row
				);
				$this->columns[$row] = array(
						'name' => $key,
						'refTableClass' => $val,
						'refColumn' => $column['name'],
						'type' => '',
						'value' => '',
						'label' => isset($column['label'])&&$column['label']?$column['label']:\Core\Camel::toCamelCase($column['name'], true, true),
						'default' => '',
						'length' => '',
						'primary' => '',
						'order' => '',
						'filter' => isset($column['filter'])?$column['filter']:'',
						'atributes' => isset($column['atributes'])?$column['atributes']:''
				);
			} else if(is_array($column) && isset($column['name']) && isset($this->info['metadata'][$column['name']])) {
				$column_name = $column['name']; 
				$this->columns_useds[$row] = array(
					'name' => $column['name'],
					'row' => $row
				);
				$this->columns[$row] = array(
						'name' => $column['name'],
						'type' => isset($column['type'])&&$column['type']?$column['type']:$this->info['metadata'][$column['name']]['DATA_TYPE'],
						'value' => isset($column['value'])&&$column['value']?$column['value']:self::DEFAULT_VALUE_STORED,
						'label' => isset($column['label'])&&$column['label']?$column['label']:\Core\Camel::toCamelCase($column['name'], true, true),
						'default' => isset($column['default'])?$column['default']:$this->info['metadata'][$column['name']]['DEFAULT'],
						'length' => isset($column['length'])?$column['length']:$this->info['metadata'][$column['name']]['LENGTH'],
						'primary' => isset($column['primary'])?$column['primary']:$this->info['metadata'][$column['name']]['PRIMARY'],
						'order' => isset($column['order'])?$column['order']:true,
						'filter' => isset($column['filter'])?$column['filter']:true,
						'atributes' => isset($column['atributes'])?$column['atributes']:''
				);
			} else if(is_string($column) && isset($this->info['metadata'][$column])) {
				$this->columns_useds[$row] = array(
					'name' => $column,
					'row' => $row
				);
				$this->columns[$row] = array(
					'name' => $column,
					'type' => $this->info['metadata'][$column]['DATA_TYPE'],
					'value' => self::DEFAULT_VALUE_STORED,
					'label' => \Core\Camel::toCamelCase($column, true, true),
					'default' => $this->info['metadata'][$column]['DEFAULT'],
					'length' => $this->info['metadata'][$column]['LENGTH'],
					'primary' => $this->info['metadata'][$column]['PRIMARY'],
					'order' => true,
					'filter' => true,
					'atributes' => ''
				);
			}
		}
		return $this;
	}
	
	public function rebuldLink($link, $data) {
		$parts = parse_url($link);
		if(isset($parts['query'])) {
			$dataQuery = array();
			parse_str($parts['query'], $dataQuery);
			$parts['query'] = urldecode(http_build_query(array_merge($dataQuery,$data)));
		} else {
			$parts['query'] = urldecode(http_build_query($data));
		}
		return $this->unparse_url ($parts);
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
	
	public function unparse_url($parsed_url) { 
		$scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : ''; 
		$host     = isset($parsed_url['host']) ? $parsed_url['host'] : ''; 
		$port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : ''; 
		$user     = isset($parsed_url['user']) ? $parsed_url['user'] : ''; 
		$pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : ''; 
		$pass     = ($user || $pass) ? "$pass@" : ''; 
		$path     = isset($parsed_url['path']) ? $parsed_url['path'] : ''; 
		$query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : ''; 
		$fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : ''; 
		return "$scheme$user$pass$host$port$path$query$fragment"; 
	} 
	
	
	/* (non-PHPdoc)
	 * @see \Core\Base\Widget::result()
	 */
	public function result() {
		$request = $this->getRequest();
		
		$data = array();
		
		if($request->getPost($this->id) == 'showcaseElements') {
			if($request->getPost('delete')) {
				$demo_user_id = \Base\Config::get('demo_user_id');
				if($demo_user_id && $demo_user_id == \User\User::getUserData()->id) {
					\Core\Session\Base::set($this->id . '-error', $this->_('You don\'t have permissions for this action!'));
				} else {
					$elements = $this->dataProvider->fetchAll($this->dataProvider->makeWhere(array($this->info['primary'][1]=>$request->getPost('delete'))));
					if($this->replaceDelete && is_callable($this->replaceDelete)) {
						if(call_user_func($this->replaceDelete, $elements, $this)) {
							\Core\Session\Base::set($this->id . '-success', $this->_('Selected rows completely deleted'));
						} else {
							\Core\Session\Base::set($this->id . '-error', $elements->getError());
						}
					} else {
						$this->dataProvider->getAdapter()->beginTransaction();
						try {
							$temp = clone $elements;
							if($elements->delete()) {
								if($this->onDelete && is_callable($this->onDelete)) {
									call_user_func($this->onDelete,$temp, $this);
								}
								$this->dataProvider->getAdapter()->commit();
								\Core\Session\Base::set($this->id . '-success', $this->_('Selected rows completely deleted'));
							} else {
								$this->dataProvider->getAdapter()->rollBack();
								if(preg_match('~Cannot\s?delete\s?or\s?update\s?a\s?parent\s?row~i', $elements->getError())) {
									\Core\Session\Base::set($this->id . '-error', $this->_('Cannot delete a row. The row has records!'));
								} else {
									\Core\Session\Base::set($this->id . '-error', $elements->getError());
								}
							}
						} catch (\Core\Exception $e) {
							$this->dataProvider->getAdapter()->rollBack();
							\Core\Session\Base::set($this->id . '-error', $e->getMessage());
						}
					}
				}
				$this->redirect($request->getServer('HTTP_REFERER'));
			}
		} else if($request->isPost() && $this->onPost && is_callable($this->onPost)) {
			$this->noLayout(true);
			$demo_user_id = \Base\Config::get('demo_user_id');
			$curObLevel = ob_get_level();
			if ($curObLevel > 0) {
				do {
					ob_get_clean();
					$curObLevel = ob_get_level();
				} while ($curObLevel > 0);
			}
			if($demo_user_id && $demo_user_id == \User\User::getUserData()->id) {
				$this->responseJsonCallback(array('error'=>$this->_('You don\'t have permissions for this action!')));
			} else {
				$this->responseJsonCallback(call_user_func($this->onPost, $this));
			}
			exit;
		}
		
		if(\Core\Session\Base::get($this->id . '-success')) {
			$data['success'] = \Core\Session\Base::get($this->id . '-success');
			\Core\Session\Base::clear($this->id . '-success');
		} else if(\Core\Session\Base::get($request->getModule() . '_form' . '-success')) {
			$data['success'] = \Core\Session\Base::get($request->getModule() . '_form' . '-success');
			\Core\Session\Base::clear($request->getModule() . '_form' . '-success');
		} else if(\Core\Session\Base::get($this->id . '-error')) {
			$data['error'] = \Core\Session\Base::get($this->id . '-error');
			\Core\Session\Base::clear($this->id . '-error');
		}
		
		if($this->orderExpr) {
			$order = new \Core\Db\Expr($this->orderExpr);
		} else {
			$order = $this->sort . ' ' .$this->order;
		}
		
		$data['results'] = $this->dataProvider->fetchAll($this->sql && $this->sql instanceof \Core\Db\Select ? $this->sql : $this->filter, $order, $this->limit, ($this->page*$this->limit)-$this->limit, $this->groupBy);
		
		if(!$this->nopagination) {
			///pagination
			$total_records = $this->dataProvider->countBy($this->sql && $this->sql instanceof \Core\Db\Select ? $this->sql : $this->filter, $this->groupBy);
			$data['page'] = $this->page;
			$data['total_records'] = $total_records;
			$data['total_pages'] = ceil($total_records / $this->limit);
			$pagination = new Pagination();
			$pagination->setLimit($this->limit);
			$pagination->setPage( $this->page );
			$pagination->setTotal($total_records);
			$pagination->setUrl( $this->rebuldLink($request->getFullUrl(),array('page' => '{page}')) );
			$data['pagination'] = $pagination->renderNew();
		}
		
		$this->render('db', $data);
		
	}
	
}