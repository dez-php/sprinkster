<?php

namespace User\Widget;

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
    protected $type = 'topuser';

    public function init() {
        $this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
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
    public function setType($type) {
        $this->type = $type;
        return $this;
    }

    /**
     * @return \Core\Db\Table\Rowset\AbstractRowset
     */
    public function getUsers() {

        $userTable = new \User\User();

        if ($this->use_index) {
            $userTable->setUseIndexes($this->use_index);
        } else if ($this->force_index) {
            $userTable->setForceIndexes($this->force_index);
        }

        // set users load limit
        if ((int) $this->limit) {
            $limit = (int) $this->limit;
        } else if ((int) $userTable->getLimit()) {
            $limit = (int) $userTable->getLimit();
        } else {
            $limit = 20;
        }

        $page = (int) $this->page > 0 ? (int) $this->page : 1;

		// set user order
        if ($this->order) {
            $order = $this->order;
        } else if ($userTable->getOrder()) {
            $order = $userTable->getOrder();
        } else {
            $order = 'id DESC';
        }
        $this->order = $order;

        if (is_array($order)) {
            $tmp = array();
            foreach ($order AS $k => $v) {
                if (strtolower($k) == 'callback') {
                    try {
                        list($field, $call) = each($v);
                        $res = call_user_func(create_function('', 'return ' . $call . ';'));
                        if($res instanceof \Core\Db\Select) {
                        	$res = $userTable->getAdapter()->fetchCol($res->limit($limit, ($page * $limit) - $limit));
                        	if ($res) {
                        		$tmp[] = 'FIELD(' . $field . ', ' . implode(',', $res) . ')';
                        	} else {
                        		$tmp[] = 'user.id DESC';
                        	}
                        } elseif (!is_array($order)) {
                            $tmp[] = 'user.id DESC';
                        } elseif ($res) {
                            $tmp[] = 'FIELD(' . $field . ', ' . implode(',', $res) . ')';
                        } else {
                            $tmp[] = 'user.id DESC';
                        }
                    } catch (\Core\Exception $e) {
                        $tmp[] = 'user.id DESC';
                    }
                } else {
                    $tmp[] = 'user.id DESC';
                }
            }
            if ($tmp) {
                $order = implode(',', $tmp);
            } else {
                $order = 'user.id DESC';
            }
        }

        $this->step_limit = $limit;
        $this->page = $page;

        $filter = $this->makeFilter();

        return $userTable->getAll($filter, $order, $limit, ($page * $limit) - $limit);
    }

    public function makeFilter() {
        $filter = null;
        if ($this->filter) {
            $userTable = new \User\User();
            if (is_array($this->filter)) {
                // search by description on search index's
                if (isset($this->filter['query'])) {
                    $wordsAll = new \Core\Text\ParseWords($this->filter['query']);
                    $words = $wordsAll->getMinLenght(2);

                    /* $where_or = array(
                      $this->filter['title'] => 'title LIKE ' . $userTable->getAdapter()->quote('%'.$this->filter['title'].'%')
                      ); */
                    $where_or[$this->filter['query']] = 'username LIKE ' . $userTable->getAdapter()->quote(str_replace(' ', '%', $this->filter['query'])) . ' OR firstname LIKE ' . $userTable->getAdapter()->quote(str_replace(' ', '%', $this->filter['query'])) . ' OR lastname LIKE ' . $userTable->getAdapter()->quote(str_replace(' ', '%', $this->filter['query']));
                    foreach ($words AS $word) {
                        $where_or[$word] = 'username LIKE ' . $userTable->getAdapter()->quote($word . '%') . ' OR firstname LIKE ' . $userTable->getAdapter()->quote($word . '%') . ' OR lastname LIKE ' . $userTable->getAdapter()->quote($word . '%');
                    }

                    if ($where_or) {
                        $filter = $this->filter;
                        $filter['where'] = '(' . implode(' OR ', $where_or) . ')';
                        unset($filter['query']);
                        $filter = $userTable->makeWhere($filter);
                    } else {
                        $filter = 'user.id = 0';
                    }
                } else {
                    $filter = $userTable->makeWhere($this->filter);
                }
            } else {
                $filter = $this->filter;
            }
        }
        return $filter;
    }

    public function countUsers() {
        $usersTable = new \User\User();

        if ($this->use_index) {
            $usersTable->setUseIndexes($this->use_index);
        } else if ($this->force_index) {
            $usersTable->setForceIndexes($this->force_index);
        }

        $filter = $this->makeFilter();

        return $usersTable->countUsers($filter);
    }

    public function result() {
        $data = [];
        
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
		
        //$self = \User\User::getUserData();
		
		//get users
        $data['users'] = $this->getUsers();

        if ((int) $this->page > 0) {
            $this->options['page'] = (int) $this->page + 1;
        } else {
            $this->options['page'] = 2;
        }
        
        $this->options['fromRow'] = ($this->page * $this->step_limit) - $this->step_limit;
        $this->options['toRow'] = $this->options['fromRow'] + $this->step_limit;

        $data['limit'] = $this->step_limit;

        $data['query'] = 'options=' . urlencode(serialize($this->options));

        $data['from_widget'] = $this->getRequest()->getRequest('widget');

        if ($this->getRequest()->isXmlHttpRequest() && $this->getRequest()->getRequest('callback')) {
            $this->responseJsonCallback($this->render($this->view, $data, true));
        } else {
            $this->render($this->view, $data);
        }
    }

}
