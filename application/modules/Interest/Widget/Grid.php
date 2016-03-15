<?php

namespace Interest\Widget;

class Grid extends \Base\Widget\PermissionWidget {

	protected $filter;
	protected $use_index;
	protected $force_index;
	protected $limit;
	protected $order;
	protected $step_limit;
	protected $options = array();
	protected $page;
	protected $total_users;
	
	protected $view = 'grid';

	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function setFilter($filter) {
		$this->filter = $filter;
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
	
	public function setPage($page) {
		$this->page = $page;
		return $this;
	}
	
	/**
	 * @return \Core\Db\Table\Rowset\AbstractRowset
	 */
	protected function getUsers() {
		
		$interestTable = new \Interest\Interest();
		
		if($this->use_index) {
			$interestTable->setUseIndexes($this->use_index);
		} else if($this->force_index) {
			$interestTable->setForceIndexes($this->force_index);
		}
		
		// filter pins by category_id
		if((int)$this->limit) {
			$limit = (int)$this->limit;
		} else if((int)$interestTable->getLimit()) {
			$limit = (int)$interestTable->getLimit();
		} else {
			$limit = 20;
		}
		
		if($this->order) {
			$order = $this->order;
		} else if($interestTable->getOrder()) {
			$order = $interestTable->getOrder();
		} else {
			$order = 'id DESC';
		}
		
		$page = (int)$this->page > 0 ? (int)$this->page : 1;
		
		if (is_array($order)) {
            $tmp = array();
            foreach ($order AS $k => $v) {
                if (strtolower($k) == 'callback') {
                    try {
                        list($field, $call) = each($v);
                        $res = call_user_func(create_function('', 'return ' . $call . ';'));
                        if($res instanceof \Core\Db\Select) {
                        	$res = $interestTable->getAdapter()->fetchCol($res->limit($limit, ($page * $limit) - $limit));
                        	if ($res) {
                        		$tmp[] = 'FIELD(' . $field . ', ' . implode(',', $res) . ')';
                        	} else {
                        		$tmp[] = 'interest.is DESC';
                        	}
                        } elseif (!is_array($order)) {
                            $tmp['interest.is DESC'] = 'interest.is DESC';
                        } elseif ($res) {
                            $tmp['FIELD(' . $field . ', ' . implode(',', $res) . ') DESC'] = 'FIELD(' . $field . ', ' . implode(',', $res) . ') DESC';
                        } else {
                            $tmp['interest.is DESC'] = 'interest.is DESC';
                        }
                    } catch (\Core\Exception $e) {
                        $tmp['interest.is DESC'] = 'interest.is DESC';
                    }
                } else {
                    if (preg_match('~^interest.([a-z0-9_]{2,})\s?(asc|desc)$~i', $v, $match)) {
                        $tmp[$v] = $v;
                    }
                }
            }
            if ($tmp) {
                $order = implode(',', $tmp);
            } else {
                $order = null;
                $this->order = null;
            }
        }
		
		$this->step_limit = $limit;
        $this->page = $page;
        
        $filter = $this->makeFilter();
		
		return $interestTable->fetchAll( $filter, $order, $limit, ($page*$limit)-$limit );
		
	}
	
	public function makeFilter() {
		$interestTable = new \Interest\Interest();
		$filter = null;
		if($this->filter) {
			if(is_array($this->filter)) {
				// search by description on search index's
				if(isset($this->filter['query'])) {
					$wordsAll = new \Core\Text\ParseWords($this->filter['query']);
					$words = $wordsAll->getMinLenght(2);
		
					$where_or[$this->filter['query']] = 'title LIKE ' . $interestTable->getAdapter()->quote(str_replace(' ', '%', $this->filter['query']));
					foreach($words AS $word) {
						$where_or[$word] = 'title LIKE ' . $interestTable->getAdapter()->quote($word.'%');
					}
						
					if($where_or) {
						$filter = $this->filter;
						$filter['where'] = '(' . implode(' OR ', $where_or) . ')';
						unset($filter['query']);
						$filter = $interestTable->makeWhere($filter);
					} else {
						$filter = 'user.id = 0';
						$limit = 0;
					}
						
				} else {
					$filter = $interestTable->makeWhere($this->filter);
				}
			} else {
				$filter = $this->filter;
			}
		}
		return $filter;
	}
	
	public function result() {
        
    	$request = $this->getRequest();
    	if (!isset($this->options['module'])) {
    		$this->options['module'] = $request->getModule();
    	}
    	
    	if (!isset($this->options['module_controller'])) {
    		$this->options['module_controller'] = $request->getModule() . '_' . $request->getController();
    	}
    	
		$data = array();
		/**
		 * get interests
		 */
		$self = \User\User::getUserData();
		$data['interests'] = $this->getUsers();

		
		if((int)$this->page > 0) {
			$this->options['page'] = (int)$this->page + 1;
		} else {
			$this->options['page'] = 2;
		}
        
        $this->options['fromRow'] = ($this->page * $this->step_limit) - $this->step_limit;
        $this->options['toRow'] = $this->options['fromRow'] + $this->step_limit;
		
		$data['limit'] = $this->step_limit;

		$data['query'] = 'options='.urlencode(serialize($this->options));
		
		$data['from_widget'] = $this->getRequest()->getRequest('widget');
		
		if( $this->getRequest()->isXmlHttpRequest() && $this->getRequest()->getRequest('callback') ) {
			$this->responseJsonCallback( $this->render($this->view ?: 'grid', $data, true) );
		} else {
			$this->render($this->view ?: 'grid', $data);
		}
	}
	
}