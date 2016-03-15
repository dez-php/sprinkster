<?php
namespace Pin\Widget;

use \Core\Base\MemcachedManager;
use \Core\Interfaces\IPersistentWidget;

class Grid extends \Base\Widget\PermissionWidget implements IPersistentWidget {

	protected $filter;
	protected $filter_data;
	protected $use_index;
	protected $force_index;
	protected $limit;
	protected $order;
	protected $step_limit;
	public $options = array();
	protected $next_max;
	protected $total_pins;
	protected $description;
	protected $createNewPin;
	protected $ignoreAjax = false;
	private $labels = array();
	private $badges = array();
	private $routes = array();
	private $widgets = array();
	private $offset = 0;
	protected $view = 'grid';
	public $event_class = 'event-masonry';
	public $pagination = true;
	public $smartscroll;
	
	private $unset = ['description', 'text-empty', 'createNewItem', 'createNewItemText', 'createNewItemLink'];

	protected $count = 0;

	public function init() {
		$this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function setFilter($filter) {
		$this->filter = array();
		if (is_array($filter)) {
			foreach ($filter AS $k => $v) {
				if ($v instanceof \Core\Db\Select) {
					$this->filter[$k] = array((string) $v);
				} else {
					$this->filter[$k] = $v;
				}
			}
		}
		return $this;
	}

	public function setUseIndex($index) {
		$this->use_index = $index;
		return $this;
	}

	public function setForceIndex($index) {
		$this->force_index = $index;
		return $this;
	}

	public function setLimit($limit) {
		$this->limit = $limit;
		return $this;
	}

	public function setOrder($order) {
		$this->order = $order;
		return $this;
	}

	public function setNext_max($next_max) {
		$this->next_max = $next_max;
		return $this;
	}

	/**
	 * @return \Core\Db\Table\Rowset\AbstractRowset
	 */
	public function filterBuilder($order = null) {
		$pinsTable = new \Pin\Pin();

		$filter = null;
		$temp_filter = $this->filter;

		$menu = \Base\PermissionMenu::getMenu('PinFilter');
		if ($menu && isset($this->options['pin_filter']) && $this->options['pin_filter'] && is_array($this->options['pin_filter'])) {
			foreach ($menu AS $row => $widget) {
				if ($widget->is_widget && array_key_exists($widget->widget, $this->options['pin_filter'])) {
					$config = array();
					if ($widget->config) {
						$config = unserialize($widget->config);
					}
					$filterData = $this->widget($widget->widget, \Core\Arrays::array_merge($config, array(
								'filter' => $this->options['pin_filter'][$widget->widget],
								'type' => 'filter',
								'module' => $this->options['module'],
								'module_controller' => $this->options['module_controller'],
								'_mca' => $this->options['_mca'],
								'self' => $this,
								'instance' => $widget
							)), true);

					if ($filterData) {
						foreach ($filterData AS $key => $data) {
							if ($key == 'callback') { 
								if (is_array($data)) {
									foreach ($data AS $k => $v) {
										if (isset($temp_filter[$key][$k])) {
											if (!is_array($temp_filter[$key][$k])) {
												$temp_filter[$key][$k] = [$temp_filter[$key][$k]];
											}
											$temp_filter[$key][$k][] = $v;
										} else {
											$temp_filter[$key][$k] = $v;
										}
									}
								}
							} else {
								$temp_filter[$key] = $data;
							}
						}
					}
				}
			}
		}
		
		if ($temp_filter) {
			$filterTemp = null;
			if (is_array($temp_filter)) {
				$filterTemp['where'] = null;
				foreach ($temp_filter AS $key => $filterData) {
					if ($key == 'description') {
						if (\Base\Config::get('database_fulltext_status') && version_compare($pinsTable->getAdapter()->getServerVersion(), '5.6', '>=')) {
							if (trim($filterData)) {
								$filter1 = "MATCH(`pin`.`title`,`pin`.`description`) AGAINST (" . $pinsTable->getAdapter()->quote($filterData) . " IN NATURAL LANGUAGE MODE)";
								if($order) {
									$order = '(('. $filter1 . ')/(LENGTH(`pin`.`title`)+LENGTH(`pin`.`description`))) DESC, ' . $order;
								} else {
									$order = '(('. $filter1 . ')/(LENGTH(`pin`.`title`)+LENGTH(`pin`.`description`))) DESC';
								}
							} else {
								$filter1 = 'pin.id = 0';
								$limit = 0;
							}
							if (!isset($filterTemp['where']['description'])) {
								$filterTemp['where']['description'] = null;
							}
							$filterTemp['where']['description'] .= $filterTemp['where']['description'] ? ' AND ' : '';
							$filterTemp['where']['description'] .= $filter1;

							$pin_ids = $this->parseDescription($filterData);
							if($pin_ids)
								$filterTemp['where']['description'] = '((' . $filterTemp['where']['description'] . ') OR pin.id IN (' . implode(',', $pin_ids) . '))';
						} else {

							$pin_ids = $this->parseDescription($filterData);
							if (isset($filterTemp['id'])) {
								if (is_array($filterTemp['id'])) {
									$filterTemp['id'] = \Core\Arrays::array_merge($filterTemp['id'], $pin_ids);
								} else {
									$filterTemp['id'] = \Core\Arrays::array_merge(array($filterTemp['id']), $pin_ids);
								}
							} else {
								$filterTemp['id'] = $pin_ids;
							}
						}
					} else if ($key == 'price') {
						$tmpWhere = null;
						if(isset($filterTemp['where']) && is_array($filterTemp['where'])) {
							$tmpWhere = $filterTemp['where'];
							$filterTemp['where'] = null;
						}
						if (is_array($filterData)) {
							if (count($filterData) == 1) {
								$price = array_shift($filterData);
								$filterTemp['where'] .= $filterTemp['where'] ? ' AND ' : '';
								$filterTemp['where'] .= '(price/(SELECT `value` FROM `currency` WHERE `code`=`pin`.`currency_code` LIMIT 1) >= ' . $pinsTable->getAdapter()->quote($price) . ')';
							} else if (count($filterData) > 1) {
								$fromPrice = array_shift($filterData);
								$toPrice = array_shift($filterData);
								if ($fromPrice && $toPrice) {
									$filterTemp['where'] .= $filterTemp['where'] ? ' AND ' : '';
									$filterTemp['where'] .= '(price/(SELECT `value` FROM `currency` WHERE `code`=`pin`.`currency_code` LIMIT 1) >= ' . $pinsTable->getAdapter()->quote($fromPrice);
									$filterTemp['where'] .= ' AND price/(SELECT `value` FROM `currency` WHERE `code`=`pin`.`currency_code` LIMIT 1) <= ' . $pinsTable->getAdapter()->quote($toPrice) . ')';
								} else if (!$fromPrice && $toPrice) {
									$filterTemp['where'] .= $filterTemp['where'] ? ' AND ' : '';
									$filterTemp['where'] .= '(price/(SELECT `value` FROM `currency` WHERE `code`=`pin`.`currency_code` LIMIT 1) <= ' . $pinsTable->getAdapter()->quote($toPrice) . ')';
								} else if ($fromPrice && !$toPrice) {
									$filterTemp['where'] .= $filterTemp['where'] ? ' AND ' : '';
									$filterTemp['where'] .= '(price/(SELECT `value` FROM `currency` WHERE `code`=`pin`.`currency_code` LIMIT 1) >= ' . $pinsTable->getAdapter()->quote($fromPrice) . ')';
								}
							}
							if($tmpWhere && $filterTemp['where']) {
								$tmpprice = $filterTemp['where'];
								$filterTemp['where'] = $tmpWhere;
								$filterTemp['where']['price'] = $tmpprice;
							}
						} else {
							$filterTemp['where'] .= $filterTemp['where'] ? ' AND ' : '';
							$filterTemp['where'] .= 'pin.id = 0';
						}
					} elseif (is_string($filterData) && strtolower($filterData) === 'null') {
						$filterTemp[$key] = null;
					} else {
						$filterTemp[$key] = $filterData;
					}
				}
				if (!$filterTemp['where']) {
					unset($filterTemp['where']);
				}
				$filter = $pinsTable->makeWhere($filterTemp);
			} else {
				$filter = $temp_filter;
			}
		}
		
		return [ $filter, $temp_filter, $order ];
	}
	
	public function getFilter($name = null) {
		return $name === null ? $this->filter : (isset($this->filter[$name]) ? $this->filter[$name] : null);
	}

	public function getPins() {
		$pinsTable = new \Pin\Pin();

		if ($this->use_index) {
			$pinsTable->setUseIndexes($this->use_index);
		} else if ($this->force_index) {
			$pinsTable->setForceIndexes($this->force_index);
		}

		// filter pins by category_id
		if ((int) $this->limit) {
			$limit = (int) $this->limit;
		} else if ((int) $pinsTable->getLimit()) {
			$limit = (int) $pinsTable->getLimit();
		} else {
			$limit = \Base\Config::get('pins_per_page');
		}
		/* if(!$this->next_max) {
		  $limit = $limit*2;
		  } */

		$page = (int) (isset($this->options['page']) ? $this->options['page'] : 1);
		if ($page < 1) {
			$page = 1;
		}
		$this->offset = $offset = ($page * $limit) - $limit;

		if ($this->order) {
			$order = $this->order;
		} else if ($pinsTable->getOrder()) {
			$order = $pinsTable->getOrder();
		} else {
			$order = 'pin.id DESC';
		}
		$this->order = $order;
		
		if (is_array($order)) {
			$tmp = array();
			foreach ($order AS $k => $v) {
				if (strtolower($k) == 'callback') {
					try {
						$db = \Core\Db\Init::getDefaultAdapter();
						list($field, $call) = each($v);
						$res = call_user_func(create_function('', 'return ' . $call . ';'));
						if($res instanceof \Core\Db\Select) {
							$resultDb = $db->fetchPairs($res);
							if($resultDb) {
								$tmp[] = 'FIELD(' . $field . ', ' . $db->quote($resultDb) . ')';
							} else {
								$tmp[] = 'pin.id DESC';
							}
						} else if (!is_array($order)) {
							$tmp[] = 'pin.id DESC';
						} else if ($res) {
							$tmp[] = 'FIELD(' . $field . ', ' . $db->quote($res) . ')';
						} else {
							$tmp[] = 'pin.id DESC';
						}
					} catch (\Core\Exception $e) {
						$tmp[] = 'pin.id DESC';
					}
				} else {
					$tmp[] = 'pin.id DESC';
				}
			}
			if ($tmp) {
				$order = implode(',', $tmp);
			} else {
				$order = 'pin.id DESC';
			}
		}
	
		$this->step_limit = $limit;
		
		$filter = $this->filterBuilder($order);

		$this->filter_data = [
			'filter' => $filter[0],
			'order' => $filter[2],
			'limit' => $limit,
			'offset' => $offset,
			'use_index' => $this->use_index,
			'repins' => isset($this->options['repins']) && $this->options['repins']
		];

		$self = $this;
		$cache_key = MemcachedManager::key(__CLASS__, __METHOD__, \User\User::getUserData()->id, $filter[0], $filter[2], $limit, $offset, $this->use_index, isset($this->options['repins']) && $this->options['repins']);

		$pins = MemcachedManager::get($cache_key, function() use($pinsTable, $filter, $limit, $offset, $self) {
			return $pinsTable->getAll($filter[0], $filter[2], $limit, $offset, $self->use_index, isset($self->options['repins']) && $self->options['repins'] );
		});
		// $pins = $pinsTable->getAll($filter[0], $filter[2], $limit, $offset, $this->use_index, isset($this->options['repins']) && $this->options['repins'] );

		return $pins;
	}

	public function countPins() {
		$pinsTable = new \Pin\Pin();

		if ($this->use_index) {
			$pinsTable->setUseIndexes($this->use_index);
		} else if ($this->force_index) {
			$pinsTable->setForceIndexes($this->force_index);
		}
		
		//select p.id from pin left join repin on (p.id = repin.pin_id) group by pin.id
		
		$filter = $this->filterBuilder(); 
		if(trim($filter[0])) { $filter[0] .= ' AND '; }
		$filter[0] .= 'pin.status = 1';
		
		$self = $this;
		$cache_key = MemcachedManager::key(__CLASS__, __METHOD__, \User\User::getUserData()->id, $filter[0], $this->use_index, isset($this->options['repins']) && $this->options['repins']);

		return MemcachedManager::get($cache_key, function() use ($pinsTable, $filter, $self) {
			$sql = $pinsTable->queryBuilder($filter[0],null,null,null,$self->use_index, isset($self->options['repins']) && $self->options['repins'])
					->reset('columns')
					->reset(\Core\Db\Select::ORDER)
					->columns(['total' => 'COUNT(pin.id)'])
					->limit(1);
			
			return $pinsTable->getAdapter()->fetchOne($sql);
		});
	}

	protected function parseDescription($description) {
		$wordsAll = new \Core\Text\ParseWords($description);
		$words = $wordsAll->getMinLenght(3);
		if ($words->count()) {
			$indexTable = new \Search\SearchIndex();
			$dataWords = array();
			$adapter = $indexTable->getAdapter();
			foreach ($words AS $word) {
				$dataWords[$word] = '`word` LIKE ' . $adapter->quote('%' . $word . '%');
			}
			$pinIndexTable = new \Pin\PinSearchIndex();

			$sql = $indexTable->getAdapter()->select()
					->from('search_index', '')
					->joinLeft('pin_search_index', 'search_index.id = pin_search_index.search_id', array('pin_id', 'pin_id'))
					->where(implode(' OR ', $dataWords))
					->limit(5000);

			$indexes = $indexTable->getAdapter()->fetchPairs($sql);
			return $indexes ? array_filter($indexes) : array(0);
		}
		return array(0);
	}

	protected function formatParameters($parts) {
		if ($parts && is_array($parts)) {
			$temp = array();
			foreach ($parts AS $part) {
				$d = explode('=>', $part);
				$temp[array_shift($d)] = implode('=>', $d);
			}
			return $temp;
		}
		return null;
	}

	public function result() {
		$request = $this->getRequest();
		$this->options['pin_filter'] = isset($this->options['pin_filter']) && $this->options['pin_filter'] && is_array($this->options['pin_filter']) ? $this->options['pin_filter'] : $request->getParam('pin_filter');
		if($request->isXmlHttpRequest() && $request->getQuery('ajax-load') == 'true') {
			$this->ajaxLoadInit();
		} else if ($request->getQuery('infscr') == 'true' || !$request->getRequest('widget')) {
			$this->infscrLoad();
		} else {
			$this->staticLoad();
		}
	}
	
	public function staticInit() {
		$data = array();
		$request = $this->getRequest();
		if (!isset($this->options['module'])) {
			if ($request->getRequest('category_id')) {
				$this->options['module'] = $request->getRequest('category_id');
			} else {
				$this->options['module'] = $request->getModule();
			}
		}
		
		if (!isset($this->options['module_controller'])) {
			if ($request->getRequest('category_id')) {
				$this->options['module_controller'] = $request->getRequest('category_id');
			} else {
				$this->options['module_controller'] = $request->getModule() . '_' . $request->getController();
			}
		}
		
		if (!isset($this->options['_mca'])) {
			$this->options['_mca'] = ['module'=>$request->getModule() ,'controller'=> $request->getController(), 'action'=>$request->getAction()];
		}

		$this->_unset();

        $this->options['page'] = 1;
		
		$this->options['fromRow'] = $this->offset;
		$this->options['toRow'] = $this->offset + $this->step_limit;
		
		$data['limit'] = $this->step_limit;
		$data['total_pins'] = $this->total_pins;

		$this->options['filter'] = $this->filter;

		$data['query'] = 'options=' . urlencode(serialize($this->options));
		$data['from_widget'] = false;

		return $this->renderBuffer($this->view, $data, null);
	}
	
	private function _unset() {
		foreach($this->unset AS $unset) {
			if (isset($this->options[$unset])) {
				unset($this->options[$unset]);
			}
		}
	}
	
	public function ajaxLoadInit() {
		$data = array();
		$request = $this->getRequest();
		if (!isset($this->options['module'])) {
			if ($request->getRequest('category_id')) {
				$this->options['module'] = $request->getRequest('category_id');
			} else {
				$this->options['module'] = $request->getModule();
			}
		}
		
		if (!isset($this->options['module_controller'])) {
			if ($request->getRequest('category_id')) {
				$this->options['module_controller'] = $request->getRequest('category_id');
			} else {
				$this->options['module_controller'] = $request->getModule() . '_' . $request->getController();
			}
		}
		
		if (!isset($this->options['_mca'])) {
			$this->options['_mca'] = ['module'=>$request->getModule() ,'controller'=> $request->getController(), 'action'=>$request->getAction()];
		}

		$this->_unset();
		
		if (isset($this->options['page'])) {
			$this->options['page'] ++;
		} else {
			$this->options['page'] = 1;
		}
		
		$this->options['fromRow'] = $this->offset;
		$this->options['toRow'] = $this->offset + $this->step_limit;
		
		$data['limit'] = $this->step_limit;
		$data['total_pins'] = $this->total_pins;

		$this->options['filter'] = $this->filter;

		$data['query'] = 'options=' . urlencode(serialize($this->options));
		$data['from_widget'] = false;
		$this->getComponent('document')->reset();
		echo $this->renderBuffer($this->view, $data);
	}

	public function infscrLoad() {
		$data = array();
		$request = $this->getRequest();
		if (!isset($this->options['module'])) {
			if ($request->getRequest('category_id')) {
				$this->options['module'] = $request->getRequest('category_id');
			} else {
				$this->options['module'] = $request->getModule();
			}
		}
		
		if (!isset($this->options['module_controller'])) {
			if ($request->getRequest('category_id')) {
				$this->options['module_controller'] = $request->getRequest('category_id');
			} else {
				$this->options['module_controller'] = $request->getModule() . '_' . $request->getController();
			}
		}
		
		if (!isset($this->options['_mca'])) {
			$this->options['_mca'] = ['module'=>$request->getModule() ,'controller'=> $request->getController(), 'action'=>$request->getAction()];
		}

		$getPins = $request->isXmlHttpRequest();
		if(!$getPins && $this->ignoreAjax) 
			$getPins = true;
		
		if ($getPins) {
			/**
			 * get pins
			 */
			$data['pins'] = $this->getPins();
			
			foreach ($data['pins'] AS $pin) {
				//labels and other
				$ext = \Pin\Helper\Ext::parse($pin); 
				foreach ($ext AS $key => $dataExt) {
					if (isset($data[$key])) {
						$data[$key] = \Core\Arrays::array_merge($data[$key], $dataExt);
					} else {
						$data[$key] = $dataExt;
					}
				}
				//labels and other
			}
		}

		$this->_unset();

		if (isset($this->options['page'])) {
			$this->options['page'] ++;
		} else {
			$this->options['page'] = 1;
		}

		$this->options['fromRow'] = $this->offset;
		$this->options['toRow'] = $this->offset + $this->step_limit;

		$data['limit'] = $this->step_limit;
		$data['total_pins'] = $this->total_pins;
		
		//$data['query'] = 'options='.base64_encode(serialize($this->options));
		$this->options['filter'] = $this->filter;
		// 		$data['query'] = http_build_query(array('options' => $this->options));
		//$data['query'] = 'options='.base64_encode(serialize($this->options));
		$data['query'] = 'options=' . urlencode(serialize($this->options));
		$data['from_widget'] = $request->getRequest('widget');

// 		$data['labels'] = $this->labels;
// 		$data['badges'] = $this->badges;
// 		$data['routes'] = $this->routes;
// 		$data['widgets'] = $this->widgets;

        if ($this->ignoreAjax) {
            $this->render($this->view . '_ajax', $data);
        } else if ($request->isXmlHttpRequest() && $request->getRequest('callback')) {
            $return = '"' . str_replace ( array (
                    "\r",
                    "\n",
                    "<",
                    ">",
                    "&"
                ), array (
                    '\r',
                    '\n',
                    '\x3c',
                    '\x3e',
                    '\x26'
                ), addslashes ( $this->render($this->view . '_ajax', $data, true) ) ) . '"';
            $this->responseJsonCallback($return, true);
        } else {
            $this->render($this->view, $data);
        }
	}

	public function staticLoad() {
		$data = array();
		$request = $this->getRequest();
		if (!isset($this->options['module'])) {
			if ($request->getRequest('category_id')) {
				$this->options['module'] = $request->getRequest('category_id');
			} else {
				$this->options['module'] = $request->getModule();
			}
		}
		
		if (!isset($this->options['module_controller'])) {
			if ($request->getRequest('category_id')) {
				$this->options['module_controller'] = $request->getRequest('category_id');
			} else {
				$this->options['module_controller'] = $request->getModule() . '_' . $request->getController();
			}
		}
		
		$data['pins'] = $this->getPins();
		$this->options['fromRow'] = $this->offset;
		$this->options['toRow'] = $this->offset + $this->step_limit;

		$data['limit'] = $this->step_limit;
		//$data['query'] = 'options='.base64_encode(serialize($this->options));
		$this->options['filter'] = $this->filter;
		// 		$data['query'] = http_build_query(array('options' => $this->options));
		$data['query'] = 'options=' . urlencode(serialize($this->options));
		$data['from_widget'] = $request->getRequest('widget');

		$data['labels'] = $this->labels;
		$data['badges'] = $this->badges;

		$this->render($this->view . '_ajax', $data);
	}

	public function onInvalidRender() {
		echo $this->_('To access this information you can subscribe to a plan.');
	}

	public function rebuldLink($link, $data) {
		$parts = parse_url($link);
		if (isset($parts['query'])) {
			$dataQuery = array();
			parse_str($parts['query'], $dataQuery);
			$parts['query'] = urldecode(http_build_query(array_merge($dataQuery, $data)));
		} else {
			$parts['query'] = urldecode(http_build_query($data));
		}
		return $this->unparse_url($parts);
	}

	public function unparse_url($parsed_url) {
		$scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
		$host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
		$port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
		$user = isset($parsed_url['user']) ? $parsed_url['user'] : '';
		$pass = isset($parsed_url['pass']) ? ':' . $parsed_url['pass'] : '';
		$pass = ($user || $pass) ? "$pass@" : '';
		$path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
		$query = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
		$fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
		return "$scheme$user$pass$host$port$path$query$fragment";
	}

}
