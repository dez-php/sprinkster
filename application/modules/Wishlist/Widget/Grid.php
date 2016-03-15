<?php

namespace Wishlist\Widget;

class Grid extends \Base\Widget\PermissionWidget {

    protected $filter;
    protected $use_index;
    protected $force_index;
    protected $limit;
    protected $order;
    protected $step_limit;
    protected $options = array();
    protected $page;
    protected $total_wishlists;
    protected $createNewWishlist;
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
    public function getWishlists() {

        $wishlistTable = new \Wishlist\Wishlist();

        if ($this->use_index) {
            $wishlistTable->setUseIndexes($this->use_index);
        } else if ($this->force_index) {
            $wishlistTable->setForceIndexes($this->force_index);
        }

        // filter pins by category_id
        if ((int) $this->limit) {
            $limit = (int) $this->limit;
        } else if ((int) $wishlistTable->getLimit()) {
            $limit = (int) $wishlistTable->getLimit();
        } else {
            $limit = 10;
        }

        if ($this->order) {
            $order = $this->order;
        } else if ($wishlistTable->getOrder()) {
            $order = $wishlistTable->getOrder();
        } else {
            $order = 'wishlist.id DESC';
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
                        	$res = $wishlistTable->getAdapter()->fetchCol($res->limit($limit, ($page * $limit) - $limit));
                        	if ($res) {
                        		$tmp[] = 'FIELD(' . $field . ', ' . implode(',', $res) . ')';
                        	} else {
                        		$tmp[] = 'wishlist.id DESC';
                        	}
                        } elseif (!is_array($order)) {
                            $tmp['wishlist.id DESC'] = 'wishlist.id DESC';
                        } elseif ($res) {
                            $tmp['FIELD(' . $field . ', ' . implode(',', $res) . ') DESC'] = 'FIELD(' . $field . ', ' . implode(',', $res) . ') DESC';
                        } else {
                            $tmp['wishlist.id DESC'] = 'wishlist.id DESC';
                        }
                    } catch (\Core\Exception $e) {
                        $tmp['wishlist.id DESC'] = 'wishlist.id DESC';
                    }
                } else {
                    if (preg_match('~^wishlist.([a-z0-9_]{2,})\s?(asc|desc)$~i', $v, $match)) {
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

        return $wishlistTable->getAll($filter, $order, $limit, ($page * $limit) - $limit);
    }

    public function makeFilter() {
        $filter = null;
        if ($this->filter) {
            if (is_array($this->filter)) {
                $wishlistTable = new \Wishlist\Wishlist();
                // search by description on search index's
                if (isset($this->filter['title'])) {
                    $wordsAll = new \Core\Text\ParseWords($this->filter['title']);
                    $words = $wordsAll->getMinLenght(2);

                    $where_or = array(
                        $this->filter['title'] => 'title LIKE ' . $wishlistTable->getAdapter()->quote('%' . $this->filter['title'] . '%')
                    );
                    foreach ($words AS $word) {
                        $where_or[$word] = 'wishlist.title LIKE ' . $wishlistTable->getAdapter()->quote('%' . $word . '%');
                    }

                    if ($where_or) {
                        $filter = $this->filter;
                        $filter['where'] = '(' . implode(' OR ', $where_or) . ')';
                        unset($filter['title']);
                        $filter = $wishlistTable->makeWhere($filter);
                    } else {
                        $filter = 'wishlist.id = 0';
                        $limit = 0;
                    }
                } else {
                    $filter = $wishlistTable->makeWhere($this->filter);
                }
            } else {
                $filter = $this->filter;
            }
        }

        return $filter;
    }

    public function countWishlists() {
        $wishlistTable = new \Wishlist\Wishlist();

        if ($this->use_index) {
            $wishlistTable->setUseIndexes($this->use_index);
        } else if ($this->force_index) {
            $wishlistTable->setForceIndexes($this->force_index);
        }

        $filter = $this->makeFilter();

        return $wishlistTable->countWishlists($filter);
    }

    public function result() {
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
    	
        /**
         * get wishlists
         */
        $self = \User\User::getUserData();
        $data['wishlists'] = $this->getWishlists();
        $data['isXmlHttpRequest'] = $this->getRequest()->isXmlHttpRequest();

        if ((int) $this->page > 0) {
            $this->options['page'] = (int) $this->page + 1;
        } else {
            $this->options['page'] = 2;
        }
        
        $this->options['fromRow'] = ($this->page * $this->step_limit) - $this->step_limit;
        $this->options['toRow'] = $this->options['fromRow'] + $this->step_limit;

        $data['limit'] = $this->step_limit;

        $data['query'] = 'options=' . base64_encode(serialize($this->options));

        $data['from_widget'] = $this->getRequest()->getRequest('widget');

        if ($data['isXmlHttpRequest'] && $this->getRequest()->getRequest('callback')) {
            $this->responseJsonCallback($this->render($this->view, $data, true));
        } else {
            $this->render($this->view, $data);
        }
    }

}
