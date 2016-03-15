<?php

namespace Category\Widget;

class Grid extends \Base\Widget\PermissionWidget {

    protected $filter;
    protected $use_index;
    protected $force_index;
    protected $limit;
    protected $order;
    protected $step_limit;
    protected $options = array();
    protected $page;
    protected $view = 'grid';

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

    /**
     * @return \Core\Db\Table\Rowset\AbstractRowset
     */
    public function getCategories() {

        $categoyTable = new \Category\Category();

        if ($this->use_index) {
            $categoyTable->setUseIndexes($this->use_index);
        } else if ($this->force_index) {
            $categoyTable->setForceIndexes($this->force_index);
        }

        // set categories load limit
        if ((int) $this->limit) {
            $limit = (int) $this->limit;
        } else if ((int) $categoyTable->getLimit()) {
            $limit = (int) $categoyTable->getLimit();
        } else {
            $limit = 20;
        }
		
		// set categoy order
        if ($this->order) {
            $order = $this->order;
        } else if ($categoyTable->getOrder()) {
            $order = $categoyTable->getOrder();
        } else {
            $order = 'category_description.title ASC';
        }
        $this->order = $order;
		
        $page = (int) $this->page > 0 ? (int) $this->page : 1;

        if (is_array($order)) {
            $tmp = array();
            foreach ($order AS $k => $v) {
                if (strtolower($k) == 'callback') {
                    try {
                        list($field, $call) = each($v);
                        $res = call_user_func(create_function('', 'return ' . $call . ';'));
                        if($res instanceof \Core\Db\Select) {
                        	$res = $categoyTable->getAdapter()->fetchCol($res->limit($limit, ($page * $limit) - $limit));
                        	if ($res) {
                        		$tmp[] = 'FIELD(' . $field . ', ' . implode(',', $res) . ')';
                        	} else {
                        		$tmp[] = 'category_description.title ASC';
                        	}
                        } elseif (!is_array($order)) {
                            $tmp[] = 'category_description.title ASC';
                        } elseif ($res) {
                            $tmp[] = 'FIELD(' . $field . ', ' . implode(',', $res) . ')';
                        } else {
                            $tmp[] = 'category_description.title ASC';
                        }
                    } catch (\Core\Exception $e) {
                        $tmp[] = 'category_description.title ASC';
                    }
                } else {
                    $tmp[] = 'category_description.title ASC';
                }
            }
            if ($tmp) {
                $order = implode(',', $tmp);
            } else {
                $order = 'category_description.title ASC';
            }
        }

        $this->step_limit = $limit;
        $this->page = $page;
		
        $filter = $this->makeFilter();

        return $categoyTable->getAll($filter, $order, $limit, ($page * $limit) - $limit);
    }

    public function makeFilter() {
        $filter = null;
        if ($this->filter) {
            $categoryTable = new \Category\Category();
            if (is_array($this->filter)) {
                // search by description on search index's
                if (isset($this->filter['query'])) {
                    $wordsAll = new \Core\Text\ParseWords($this->filter['query']);
                    $words = $wordsAll->getMinLenght(2);

                    /* $where_or = array(
                      $this->filter['title'] => 'title LIKE ' . $categoryTable->getAdapter()->quote('%'.$this->filter['title'].'%')
                      ); */
                    $where_or[$this->filter['query']] = 'username LIKE ' . $categoryTable->getAdapter()->quote(str_replace(' ', '%', $this->filter['query'])) . ' OR firstname LIKE ' . $categoryTable->getAdapter()->quote(str_replace(' ', '%', $this->filter['query'])) . ' OR lastname LIKE ' . $categoryTable->getAdapter()->quote(str_replace(' ', '%', $this->filter['query']));
                    foreach ($words AS $word) {
                        $where_or[$word] = 'username LIKE ' . $categoryTable->getAdapter()->quote($word . '%') . ' OR firstname LIKE ' . $categoryTable->getAdapter()->quote($word . '%') . ' OR lastname LIKE ' . $categoryTable->getAdapter()->quote($word . '%');
                    }

                    if ($where_or) {
                        $filter = $this->filter;
                        $filter['where'] = '(' . implode(' OR ', $where_or) . ')';
                        unset($filter['query']);
                        $filter = $categoryTable->makeWhere($filter);
                    } else {
                        $filter = 'categoy.id = 0';
                    }
                } else {
                    $filter = $categoryTable->makeWhere($this->filter);
                }
            } else {
                $filter = $this->filter;
            }
        }
        return $filter;
    }

    public function countCategories() {
        $categoriesTable = new \Category\Category();

        if ($this->use_index) {
            $categoriesTable->setUseIndexes($this->use_index);
        } else if ($this->force_index) {
            $categoriesTable->setForceIndexes($this->force_index);
        }

        $filter = $this->makeFilter();

        return $categoriesTable->countCategories($filter);
    }

    public function result() {
        
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
    	
        $data = [];
		
		//get categories
        $data['categories'] = $this->getCategories();

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
