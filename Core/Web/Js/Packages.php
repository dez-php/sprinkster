<?php
return array(
	'jquery'=>array(
		'js'=>array('window.jQuery' => \Core\Base\Action::getInstance()->getApplication()->getEnvironment() != 'production' ? 'jquery.js' : 'jquery.min.js'),
	),
	'core'=>array(
			'js'=>array('core/jquery.core.js'),
			'depends'=>array('jquery'),
	),
	'coretab'=>array(
			'js'=>array('core/jquery.coretab.js'),
			'depends'=>array('jquery'),
	),
	'coreactiveform'=>array(
			'js'=>array('core/jquery.coreactiveform.js'),
			'depends'=>array('jquery'),
	),
	'jquery.ui'=>array(
		'js'=>array('jui/js/jquery-ui.min.js'),
		'depends'=>array('jquery'),
	),
	'bgiframe'=>array(
		'js'=>array('jquery.bgiframe.js'),
		'depends'=>array('jquery'),
	),
	'ajaxqueue'=>array(
		'js'=>array('jquery.ajaxqueue.js'),
		'depends'=>array('jquery'),
	),
	'autocomplete'=>array(
		'js'=>array('jquery.autocomplete.js'),
		'depends'=>array('jquery', 'bgiframe', 'ajaxqueue'),
	),
	'maskedinput'=>array(
		'js'=>array(\Core\Base\Action::getInstance()->getApplication()->getEnvironment() != 'production' ? 'jquery.maskedinput.js' : 'jquery.maskedinput.min.js'),
		'depends'=>array('jquery'),
	),
	'cookie'=>array(
		'js'=>array('jquery.cookie.js'),
		'depends'=>array('jquery'),
	),
	'treeview'=>array(
		'js'=>array('jquery.treeview.js', 'jquery.treeview.edit.js', 'jquery.treeview.async.js'),
		'depends'=>array('jquery', 'cookie'),
	),
	'multifile'=>array(
		'js'=>array('jquery.multifile.js'),
		'depends'=>array('jquery'),
	),
	'rating'=>array(
		'js'=>array('jquery.rating.js'),
		'depends'=>array('jquery', 'metadata'),
	),
	'metadata'=>array(
		'js'=>array('jquery.metadata.js'),
		'depends'=>array('jquery'),
	),
	'bbq'=>array(
		'js'=>array(\Core\Base\Action::getInstance()->getApplication()->getEnvironment() != 'production' ? 'jquery.ba-bbq.js' : 'jquery.ba-bbq.min.js'),
		'depends'=>array('jquery'),
	),
	'history'=>array(
		'js'=>array('jquery.history.js'),
		'depends'=>array('jquery'),
	),
);
