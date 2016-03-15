<?php

namespace Base;

use Core\Reflection\Method;

class Event extends \Base\Model\Reference {
	
	protected function getActions($namespace) {
		$namespace = explode('.',$namespace);
		$prefix = array_shift($namespace);
		if($prefix) {
			if($namespace) {
				$tmp = array();
				foreach($namespace AS $ns) {
					$tmp[] = '`namespace` LIKE "' . $prefix . '.%' . $ns . '%"';
				}
				$where['where'] = implode(' OR ', $tmp);
			} else {
				$where = array('namespace' => new \Core\Db\Expr('"' . $prefix . '.%"'));
			}
		} else {
			$where = array('id' => 0);
		}
		return $this->fetchAll($this->makeWhere($where));
	}
	
	public static function trigger() {
		ignore_user_abort(true);
		set_time_limit(0);
		$args = func_get_args ();
		if (empty ( $args )) {
			return false;
		}
		$callto = array_shift ( $args );
		$self = new self();
		$events = $self->getActions($callto);
		if($events->count()) {
			foreach($events AS $event) {
				call_user_func_array(array('\Core\Http\Thread','run'), array_merge(array(array($event->class, $event->method)),$args));
			}
		}
	}
	
}