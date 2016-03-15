<?php

namespace Country;

class Country extends \Base\Model\Reference {

    /**
     * Set global Table rowset order
     *
     * @var string \Core\Db\Expr
     */
    protected $_order = 'name ASC';

    public function autocomplete($query) {
        if(!trim($query))
            return [];
        $adapter = $this->getAdapter();
        $sql = $this->select()
            ->where('name LIKE ?', new \Core\Db\Expr($adapter->quote($query . '%')))
            ->limit(200);
        $result = [];
        foreach($this->fetchAll($sql) AS $row) {
            $result[] = [
                'id'	=> $row->id,
                'name'	=> $row->name
            ];
        }
        return $result;
    }

    public function autocomplete_search($query) {
        if(!trim($query))
            return null;
        $adapter = $this->getAdapter();
        $sql = $this->select()
            ->from($this, 'id')
            ->where('name LIKE ?', new \Core\Db\Expr($adapter->quote('%' . $query . '%')));

        return $sql;
    }

}