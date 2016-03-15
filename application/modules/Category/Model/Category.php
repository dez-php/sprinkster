<?php

namespace Category;

class Category extends \Base\Model\Reference {

    public function __construct($config = array()) {
        parent::__construct($config);
        $this->setRowClass('\Category\CategoryRow');
    }

    protected $_referenceMap    = [
        'CategoryDescription' => [
            'columns'           => 'id',
            'refTableClass'     => 'Category\CategoryDescription',
            'refColumns'        => 'category_id',
            'where'				=> '"language_id = " . \Core\Base\Action::getModule(\'Language\')->getLanguageId()'
        ],
        'Pin' => [
            'columns'           => 'id',
            'refTableClass'     => 'Pin\Pin',
            'refColumns'        => 'category_id',
        ],
        'Follow' => [
            'columns'           => 'id',
            'refTableClass'     => 'Category\CategoryFollow',
            'refColumns'        => 'category_id',
        ]
    ];
    /**
     * @param null|number $parent_id
     * @return multitype:
     */
    public static function getAllIdTitle($parent_id = null) {
        $db = \Core\Db\Init::getDefaultAdapter();
        $sql = $db->select()
            ->from('category', array('id', 'image'))
            ->joinLeft('category_description', 'category.id = category_description.category_id', array('title',new \Core\Db\Expr('(SELECT COUNT(c.id) FROM category c WHERE c.parent_id = category.id LIMIT 1) AS subcategory')))
            ->where('parent_id' . (is_null($parent_id) ? ' IS NULL' : ' = ?'), $parent_id)
            ->where('status = 1')
            ->where('category_description.language_id = ?', \Core\Base\Action::getModule('Language')->getLanguageId())
            ->order(new \Core\Db\Expr('sort_order ASC, category_description.title ASC'));

        $sql->columns(array('show'=>new \Core\Db\Expr('"pin"')));

        //extend sql query
        $self = new self();
        $extendTable = new \Base\Extend();
        $extends = $extendTable->getExtension('Category\getAll', 'order');
        if($extends) {
            $front = \Core\Base\Front::getInstance();
            foreach($extends AS $extend) {
                $objectName = $front->formatHelperName($extend->extend);
                try {
                    if(class_exists($objectName)) {
                        $object = new $objectName($sql, $extend, $self);
                        if($object instanceof \Category\CategoryOrderAbstract) {
                            $sql = $object->getExtendetSql();
                        }
                    }
                } catch (\Core\Exception $e) {}
            }
        }
        //end extend sql query

        $rows = $db->fetchAll($sql);

        $data  = array(
            'table'    => $self,
            'data'     => $rows,
            'readOnly' => true,
            'rowClass' => $self->getRowClass(),
            'stored'   => true
        );

        $rowsetClass = $self->getRowsetClass();
        if (!class_exists($rowsetClass)) {
            require_once 'Loader.php';
            \Core\Loader\Loader::loadClass($rowsetClass);
        }

        return new $rowsetClass($data);
    }

    /**
     * @param number $id
     * @return multitype:
     */
    public static function get($id, $status = true) {
        $db = \Core\Db\Init::getDefaultAdapter();
        $sql = $db->select()
            ->from('category')
            ->joinLeft('category_description', 'category.id = category_description.category_id', array('title','description','meta_title','meta_description','meta_keywords'))
            ->where('category.id = ?', $id)

            ->where('category_description.language_id = ?', \Core\Base\Action::getModule('Language')->getLanguageId());
        if($status) {
            $sql->where('status = 1');
        }

        $sql->columns(array('show'=>new \Core\Db\Expr('"pin"')));
        $self = \User\User::getUserData();
        if($self->id) {
            $sql->columns(array('following_category'=>new \Core\Db\Expr('('.$db->select()->from('category_follow', 'COUNT(1)')->where('user_id = ?', $self->id)->where('category_id = category.id')->limit(1).')')));
        } else {
            $sql->columns(array('following_category'=>new \Core\Db\Expr('0')));
        }


        //extend category query
        $self = new self();
        $extendTable = new \Base\Extend();
        $extends = $extendTable->getExtension('Category\getAll', 'order');
        if($extends) {
            $front = \Core\Base\Front::getInstance();
            foreach($extends AS $extend) {
                $objectName = $front->formatHelperName($extend->extend);
                try {
                    if(class_exists($objectName)) {
                        $object = new $objectName($sql, $extend, $self);
                        if($object instanceof \Category\CategoryOrderAbstract) {
                            $sql = $object->getExtendetSql();
                        }
                    }
                } catch (\Core\Exception $e) {}
            }
        }
        //end extend category query

        $rows = $db->fetchRow($sql);

        if (!$rows) {
            return null;
        }

        $data = array(
            'table'   => $self,
            'data'     => $rows,
            'readOnly' => true,
            'stored'  => true
        );


        $rowClass = $self->getRowClass();
        if (!class_exists($rowClass)) {
            require_once 'Loader.php';
            \Core\Loader\Loader::loadClass($rowClass);
        }
        return new $rowClass($data);
    }

    public function getAll($where = null, $order = null, $count = null, $offset = null) {

        $db = \Core\Db\Init::getDefaultAdapter();
        $sql = $db->select()
            ->from('category')
            ->joinLeft('category_description', 'category.id = category_description.category_id', array('title','description','meta_title','meta_description','meta_keywords'))
            ->where('status = 1')
            ->where('category_description.language_id = ?', \Core\Base\Action::getModule('Language')->getLanguageId())
            ->order($order ?: new \Core\Db\Expr('category.sort_order ASC, category_description.title ASC'))
            ->limit($count, $offset);

        if($where)
            $sql->where($where);

        $sql->columns(array('show'=>new \Core\Db\Expr('"pin"')));
        $self = \User\User::getUserData();
        if($self->id) {
            $sql->columns(array('following_category'=>new \Core\Db\Expr('('.$db->select()->from('category_follow', 'COUNT(1)')->where('user_id = ?', $self->id)->where('category_id = category.id')->limit(1).')')));
        } else {
            $sql->columns(array('following_category'=>new \Core\Db\Expr('0')));
        }


        //extend category query
        $self = new self();
        $extendTable = new \Base\Extend();
        $extends = $extendTable->getExtension('Category\getAll', 'order');
        if($extends) {
            $front = \Core\Base\Front::getInstance();
            foreach($extends AS $extend) {
                $objectName = $front->formatHelperName($extend->extend);
                try {
                    if(class_exists($objectName)) {
                        $object = new $objectName($sql, $extend, $self);
                        if($object instanceof \Category\CategoryOrderAbstract) {
                            $sql = $object->getExtendetSql();
                        }
                    }
                } catch (\Core\Exception $e) {}
            }
        }
        //end extend category query

        $rows = $db->fetchAll($sql);

        $data  = array(
            'table'    => $this,
            'data'     => $rows,
            'readOnly' => true,
            'rowClass' => $this->getRowClass(),
            'stored'   => true
        );

        $rowsetClass = $this->getRowsetClass();
        if (!class_exists($rowsetClass)) {
            require_once 'Loader.php';
            \Core\Loader\Loader::loadClass($rowsetClass);
        }

        return new $rowsetClass($data);
    }

    public function countCategories($where = null) {

        $db = \Core\Db\Init::getDefaultAdapter();
        $sql = $db->select()
            ->from('category','COUNT(DISTINCT category.id) AS total')
            ->joinLeft('category_description', 'category.id = category_description.category_id', '')
            ->where('status = 1')
            ->where('category_description.language_id = ?', \Core\Base\Action::getModule('Language')->getLanguageId())
            ->limit(1);

        if($where)
            $sql->where($where);

        //extend category query
        $self = new self();
        $extendTable = new \Base\Extend();
        $extends = $extendTable->getExtension('Category\getAll', 'order');
        if($extends) {
            $front = \Core\Base\Front::getInstance();
            foreach($extends AS $extend) {
                $objectName = $front->formatHelperName($extend->extend);
                try {
                    if(class_exists($objectName)) {
                        $object = new $objectName($sql, $extend, $self);
                        if($object instanceof \Category\CategoryOrderAbstract) {
                            $sql = $object->getExtendetSql();
                        }
                    }
                } catch (\Core\Exception $e) {}
            }
        }
        //end extend category query

        return $db->fetchOne($sql);
    }

    /**
     * @param number $limit
     * @return Ambigous <multitype:, multitype:unknown >
     */
    public static function getSitemap($limit = 1000000) {
        $db = \Core\Db\Init::getDefaultAdapter();
        $sql = $db->select()
            ->from('category', 'id')
            ->joinLeft('category_description', 'category.id = category_description.category_id', 'title')
            ->where('status = 1')
            ->where('category_description.language_id = ?', \Core\Base\Action::getModule('Language')->getLanguageId())
            ->order('category.id DESC')
            ->limit($limit);
        return $db->fetchPairs($sql);
    }

    /**
     * @param number $id
     * @param string $separator
     * @param string $title
     * @return string
     */
    public static function getPathFromChild($id, $separator = ' / ', $title = '') {
        $cat_info = self::get($id, false);
        if($cat_info) {
            if($cat_info->parent_id) {
                $title = $cat_info->title . ($title ? $separator : '') . $title;
                return self::getPathFromChild($cat_info->parent_id, $separator, $title);
            } else {
                $title = $cat_info->title . ($title ? $separator . $title : '');
            }
        }
        return $title;
    }

    /**
     * @param number|null $parent_id
     * @param number $level
     * @return Ambigous <multitype:, multitype:unknown >
     */
    public function getAllInLevels($parent_id = null, $level = -1, $formated = false, $nullRow = false, $ignore = null, $decode = false) {
        $categoryTable = new \Category\Category();
        $categories = $categoryTable->getAllIdTitle($parent_id);
        $return = array();
        if($nullRow && $formated) {
            $return[0] = '';
        }
        if($categories) {
            $level++;
            foreach($categories AS $category) {
                $childs = $this->getAllInLevels($category->id, $level, $formated, false, $ignore);
                if($childs) {
                    $category->disabled = true;
                } else {
                    $category->disabled = false;
                }
                $category->title = str_pad('', $level, "-", STR_PAD_LEFT) . ($decode ? html_entity_decode($category->title, ENT_QUOTES,'utf-8') : $category->title);
                if($formated) {
                    if(!$nullRow) {
                        $return[$category->id] = $category->title;
                    } else if($ignore != $category->id) {
                        $return[$category->id] = $category->title;
                    } else {
                        $childs = false;
                        $category->disabled = true;
                    }
                } else {
                    $return[] = $category;
                }
                if($childs) { $return = \Core\Arrays::array_merge($return,$childs); }
            }
        }
        return $return;
    }

    public static function getCategoryTreeSelect($parent_id = null, $level = -1) {
        $self = new self();
        $categories = $self->getAllIdTitle($parent_id);
        $return = array();
        if($categories) {
            $level++;
            foreach($categories AS $category) {
                $childs = self::getCategoryTreeSelect($category->id, $level);
                $return[$category->id.'.0'] = array(
                    'id' => $category->id,
                    'title' => $self->getPathFromChild($category->id, ' -> ')
                );
                if($childs) { $return = \Core\Arrays::array_merge($return,$childs); }
            }
        }
        return $return;
    }

    /*public static function getCategories($parent_id = NULL)
    {
        $cids = \Core\Db\Init::getDefaultAdapter()->fetchCol('
                SELECT c.id
                FROM category c
                JOIN category_description d ON (c.id = d.category_id)
                WHERE
                (
                        (c.parent_id IS NULL AND ? IS NULL)
                    OR
                        (c.parent_id = ?)
                )
                ORDER BY d.title
            ',

            [ $parent_id, (int) $parent_id ]
        );

        array_walk($cids, 'intval');

        if(empty($cids) || (1 == count($cids) && NULL === $cids[0]))
            $cids = [ 0 ];

        $categories = (new \Category\Category)->fetchAll([ 'id IN (' . implode(',', $cids) . ')' ]);

        if(!$categories)
            return $categories;

        return $categories;
    }

    public static function getTree($parent_id = NULL, $depth = NULL, $level = 0)
    {
        if(0 < (int) $depth && $level > (int) $depth)
            return NULL;

        $categories = self::getCategories($parent_id);
        $result = [];

        if(!$categories || 0 >= $categories->count())
            return NULL;

        foreach($categories as $category)
        {
            $dynamic = \Core\Db\Table\Row\DynamicRow::createFromAbstractRow($category);
            $dynamic->mapdata = $dynamic->findDependentRow('Category\CategoryDescription');

            $dynamic->level = $level;
            $dynamic->subcategories = self::getTree($dynamic->id, $depth, $level + 1);

            $result[] = $dynamic;
        }

        return $result;
    }*/

    public static function getCategories($parent_id = NULL, $active = false)
    {
		$active = $active ? '= 1 ' : 'IS NOT NULL ';
		
		$rows = \Core\Db\Init::getDefaultAdapter()->fetchAll('
                SELECT 
					d.*,
					c.*,
					(SELECT COUNT(id) FROM category WHERE parent_id = c.id LIMIT 1) AS children
                FROM category c
                JOIN category_description d ON (c.id = d.category_id)
                WHERE
                (
                        (c.parent_id IS NULL AND ? IS NULL)
                    OR
                        (c.parent_id = ?)
                )
				AND
					c.status ' . $active . '
				AND
					d.language_id = ' . (int)\Core\Base\Action::getModule('Language')->getLanguageId() . '
                ORDER BY c.sort_order ASC, d.title ASC
            ',
			[ $parent_id, (int) $parent_id ]
		);
		
		$self = new self();
		
		$data  = array(
				'table'    => $self,
				'data'     => $rows,
				'readOnly' => true,
				'rowClass' => $self->getRowClass(),
				'stored'   => true
		);
		
		$rowsetClass = $self->getRowsetClass();
		if (!class_exists($rowsetClass)) {
			require_once 'Loader.php';
			\Core\Loader\Loader::loadClass($rowsetClass);
		}
		
		return new $rowsetClass($data);

    }

    public static function getTree($parent_id = NULL, $depth = NULL, $level = 0)
    {
        if(0 < (int) $depth && $level > (int) $depth)
            return NULL;

        $categories = self::getCategories($parent_id, true);

        $result = [];

        if(!$categories || 0 >= $categories->count())
            return NULL;

        foreach($categories as $category)
        {
            $dynamic = \Core\Db\Table\Row\DynamicRow::createFromAbstractRow($category);

            $dynamic->level = $level;
            $dynamic->subcategories = $dynamic->children ? self::getTree($dynamic->id, $depth, $level + 1) : [];

            $result[] = $dynamic;
        }

        return $result;
    }

    /////////////
    public function autocomplete($query) {
        if(!trim($query))
            return [];
        $adapter = $this->getAdapter();
        $sql = $adapter->select()
            ->from('category', 'id')
            ->joinLeft('category_description', 'category_description.category_id = category.id', 'title')
            ->where('category_description.language_id = ?', (int)\Core\Base\Action::getModule('Language')->getLanguageId())
            ->where('title LIKE ?', new \Core\Db\Expr($adapter->quote('%' . $query . '%')))
            ->limit(200)
            ->order('category_description.title');
        $result = [];
        foreach($adapter->fetchAll($sql) AS $row) {
            $result[] = [
                'id'	=> $row['id'],
                'name'	=> $row['title']
            ];
        }
        return $result;
    }

    public function autocomplete_search($query) {
        if(!trim($query))
            return null;
        $adapter = $this->getAdapter();
        $sql = $adapter->select()
            ->from('category', 'id')
            ->joinLeft('category_description', 'category_description.category_id = category.id', '')
            ->where('category_description.language_id = ?', (int)\Core\Base\Action::getModule('Language')->getLanguageId())
            ->where('title LIKE ?', new \Core\Db\Expr($adapter->quote('%' . $query . '%')));

        return $sql;
    }

}