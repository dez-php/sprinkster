<?php

namespace Widget\Grid;

class Db2 extends \Core\Base\Widget {

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
	protected $nosearch = false;
	protected $nopagination = false;
	
	protected $referenceRowNameAuto = array('title', 'name', 'username', 'email', 'firstname', 'description', 'comment');
	
	protected $limit = 20;
	protected $order;
	protected $sort;
	protected $page = 1;
	protected $has_edit = true;
	protected $has_delete = true;
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

	const DEFAULT_VALUE_STORED = 'DEFAULT_VALUE_STORED';
	const ROW_AUTO_GET_NOT_FOUND = 'ROW_AUTO_GET_NOT_FOUND';
	
	public function init() { 
		$this->_ = new \Translate\Locale(__NAMESPACE__, $this->getModule('Language')->getLanguageId());
	}
	
	public function setOrder($order) {
		$this->order = $order;
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
		
		if(!$this->id) {
			$this->id = $this->info['name'] . '-grid';
			$form = new \Core\Htmlform\Htmlform($this->info['name'] . '_form');
			$form->clearSession();
		}
		
		if( !in_array(strtolower($this->order), array('asc','desc')) ) {
			if($request->getQuery('order') && strtolower($request->getQuery('order')) == 'asc') {
				$this->order = 'ASC';
			} else {
				$this->order = 'DESC';
			}
		}
		
		if($request->getQuery('sort') && isset($this->columns_useds[$request->getQuery('sort')])) {
			$this->sort = $request->getQuery('sort');
		} else if($this->sort && (isset($this->columns_useds[$this->sort]) || $this->sort instanceof \Core\Db\Expr)) {
			
		} else {
			$this->sort = $this->info['primary'][1];
		}

		$this->page = (int)$request->getQuery('page') > 0 ? (int)$request->getQuery('page') : 1;

// 		if(!$this->columns_useds) {
// 			$this->setColumns($this->info['cols']);
// 		}
	}
	
	protected function valueType($column) {
		if(isset($column['value'])) {
			if(is_string($column['value'])) {
				if(strpos($column['value'], '.') !== false) {
					list($class, $col) = explode('.',$column['value']);
					if(isset($this->info['referenceMap'][$class])) {
						return function($data) use($column, $class, $col) { 
							$reference = $data->$class();
							if(!$col) {
								return $reference;
							} else {
								if(method_exists($reference, $col) && is_callable(array($reference, $col))) {
									return call_user_func(array($reference, $col));
								} else if(isset($reference->{$col})) {
									return $reference->{$col};
								}
								return null; 
							}
							return isset($data->{$column['name']}) ? $data->{$column['name']} : null; 
						};
					}
					return function($data) use($column) { return $column['value']; };
				} else {
					return function($data) use($column) { return $column['value']; };
				}
			} else if($column['value'] instanceof \Closure) {
				return $column['value'];
			} else {
				return function($data) use($column) { return isset($data->{$column['name']}) ? $data->{$column['name']} : null; };
			}
		} else {
			return function($data) use($column) { return isset($data->{$column['name']}) ? $data->{$column['name']} : null; };
		}
	}
	
	protected function setFilterColum($column) {
		if(isset($column['filter'])&& $column['filter']||!isset($column['filter'])) {
			if(is_string($column['value'])) {
				if(strpos($column['value'], '.') !== false) {
					if( count($parts = explode('.',$column['value'])) > 1 ) {
						if(isset($this->info['referenceMap'][$parts[0]])) {
							$reference = $this->info['referenceMap'][$parts[0]];
							$refData = $this->dataProvider->findRowset($reference['refTableClass'], array(get_class($this->dataProvider) => $reference));
							
							if($refData->count()) {
								$list = array();
								foreach($refData AS $r) { 
									if(method_exists($r, $parts[1]) && is_callable(array($r, $parts[1]))) {
										$list[$r->{$reference['refColumns']}] = call_user_func(array($r, $parts[1]));
									} else if(isset($r->{$parts[1]})) {
										$list[$r->{$reference['refColumns']}] = $r->{$parts[1]};
									}
								}
								return \Core\Html::activeDropDownList($this, $column['name'], $list, array(
									'empty' => $this->_('Select'),
									'key' => $column['name']
								));
							}
						}
					}
					return '';
				}
			}
			return \Core\Html::activeTextField($this, $column['name']);
		}
		return null;
	}
	
	public function hasErrors($col) {
		
	}
	
	protected function makeSortLink($column) {
		if(isset($column['sort'])) {
			if(is_callable($column['sort'])) {
				return $column['sort']();
			} else {
				return $column['label'];
			}
		} else {
			$sort = 'sort';
			$order = 'order';
			if($column['name'] == $this->sort) {
				$sort_order = array(\Core\Html::resolveName(__CLASS__,$order) => ($this->order == 'ASC' ? 'DESC' : 'ASC'));
				$class = 'sort-'.strtolower($this->order);
			} else {
				$sort_order = array(\Core\Html::resolveName(__CLASS__,$sort) => $column['name'], \Core\Html::resolveName(__CLASS__,$order) => $this->order);
				$class = '';
			}
			$link = $this->rebuldLink($this->getRequest()->getFullUrl(),$sort_order);
			return \Core\Html::link($column['label'], $link, array('class' => $class));
		}
	}
	
	protected function mergeColumnDefault($column) {
		if(!is_array($column)) {
			$column = array(
					'name' => $column,
					'atributes' => null,
					'label' => \Core\Camel::toCamelCase($column, true, true),
					'value' => function($data) use($column) { return isset($data->{$column}) ? $data->{$column} : null; },
					'class' => '\Widget\Grid\Db2\DataColumn'
			);
		}
		if(!isset($column['class'])) {
			$column['class']='\Widget\Grid\Db2\DataColumn';
		}
		
// 		$column = \Core\Registry\Component::create($column, $this);

// 		if(!$column->visible)
// 		{
// 			return false;
// 		}
// 		$id=$this->getId();
// 		if($column->id===null)
// 			$column->id=$id.'_c'.$i;
		
// 		$column->init();
// 		var_dump($column); exit;
// 		return $column;
		
		
		if(isset($column['name'])) {
			return array(
					'name' => $column['name'],
					'order' => isset($column['order'])?$column['order']:true,
					'filter' => $this->setFilterColum($column),
					'atributes' => isset($column['atributes'])?$column['atributes']:null,
					'label' => isset($column['label'])?$column['label']:\Core\Camel::toCamelCase($column, true, true),
					'value' => $this->valueType($column),
					'sort' => $this->makeSortLink($column)
			);
			
		}
		return false;
	}
	
	public function setColumnsUseds($columns = array()) {
		
		$filters = $this->getRequest()->getQuery(\Core\Html::modelName(__CLASS__));
		if($filters) {
			foreach($filters AS $col => $filter) {
				$this->{$col} = $filter;
			}
		}
		
		$this->columns_useds = array();
		$this->info = $this->info ? $this->info : $this->dataProvider->info();
		if(!$columns) { $columns = $this->info['cols']; }
		foreach($columns AS $column) {
			$col = $this->mergeColumnDefault($column);
			if($col) {
				$this->columns_useds[] = $col;
			}
		}
		
		return $this;
	}
	
	private function parseArrayKeyString($in) {
		if(is_array($in)) {
			list($key, $value) = each($in);
			if(strpos($key, '[')) {
				$parts = array();
				parse_str($key.'='.$value, $parts);
				return $parts;
			}
			return array($key => $value);
		}
		return false;
	}
	
	public function rebuldLink($link, $data) {
		$parts = parse_url($link);
		if(isset($parts['query'])) {
			$dataQuery = array();
			parse_str($parts['query'], $dataQuery);
			$parts['query'] = urldecode(http_build_query(\Core\Arrays::array_merge($dataQuery,$this->parseArrayKeyString($data))));
		} else {
			$parts['query'] = urldecode(http_build_query($this->parseArrayKeyString($data)));
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
		
		$this->setColumnsUseds($this->columns);
		
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
		
		$data['results'] = $this->dataProvider->fetchAll($this->filter, $order, $this->limit, ($this->page*$this->limit)-$this->limit);
		
		
		///pagination
		$total_records = $this->dataProvider->countBy($this->filter);
		$data['page'] = $this->page;
		$data['total_records'] = $total_records;
		$data['total_pages'] = ceil($total_records / $this->limit);
		$pagination = new Pagination();
		$pagination->setLimit($this->limit);
		$pagination->setPage( $this->page );
		$pagination->setTotal($total_records);
		$pagination->setUrl( $this->rebuldLink($request->getFullUrl(),array('page' => '{page}')) );
		$data['pagination'] = $pagination->render();
		
		
		$this->render('db', $data);
		
	}
	
}