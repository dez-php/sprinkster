<?php

namespace Base;

class FormExtend extends \Base\Model\Reference {
	
	/**
	 * @param string $module
	 * @param string $type
	 * @param string $order
	 * @return \Core\Db\Table\Rowset\AbstractRowset
	 */
	public static function getExtension($namespace, $order = 'sort_order ASC') {
		if(is_array($namespace)) {
			$self = new self();
			$where = array();
			foreach($namespace AS $r => $nsp) {
				$nsp = explode('.',$nsp);
				$prefix = array_shift($nsp);
				if($prefix) {
					if($nsp) {
						$tmp = array('`form_name` LIKE "' . $prefix . '"');
						$t = '';
						while ( count($nsp) ) {
							$ns = array_shift($nsp);
							$t .= '.%' . $ns . '%';
							$tmp[] = '`form_name` LIKE "' . $prefix . $t . '"';
						}
						$where[$r] = implode(' OR ', $tmp);
					} else {
						$where[$r] = array('form_name' => new \Core\Db\Expr('"' . $prefix . '"'));
					}
				}
			}
			if($where) {
				return $self->fetchAll('(' . implode(') OR (', $where) . ')', $order);
			}
			return array();
		} else {
			$self = new self();
			$namespace = explode('.',$namespace);
			$prefix = array_shift($namespace);
			if($prefix) {
				if($namespace) {
					$tmp = array('`form_name` LIKE "' . $prefix . '"');
					foreach($namespace AS $ns) {
						$tmp[] = '`form_name` LIKE "' . $prefix . '.%' . $ns . '%"';
					}
					$where['where'] = implode(' OR ', $tmp);
				} else {
					$where = array('form_name' => new \Core\Db\Expr('"' . $prefix . '"'));
				}
			} else {
				$where = array('id' => 0);
			}
			$where['status'] = 1;
			return $self->fetchAll($self->makeWhere($where), $order);
		}
	}
	
}