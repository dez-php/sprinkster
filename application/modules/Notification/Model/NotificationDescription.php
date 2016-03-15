<?php

namespace Notification;

class NotificationDescription extends \Base\Model\Reference {

	protected $_referenceMap    = array(
			'Notification' => array(
					'columns'           => 'notification_id',
					'refTableClass'     => 'Notification\Notification',
					'refColumns'        => 'id',
					'singleRow'			=> true
			),
	);
	
}