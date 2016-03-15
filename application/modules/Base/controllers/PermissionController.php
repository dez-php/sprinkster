<?php

namespace Base;

use \Base\Traits\AcceptedResponseDetection;

abstract class PermissionController extends \Core\Base\Action {

	/**
	 * Action methods that will be assumed public for this controller
	 * @var array Action names without 'Action' postfix
	 */
	protected $_public_actions = [];

	const PermissionSeparator = '.';

	use AcceptedResponseDetection;

	public function __construct()
	{
		parent::__construct();
		$this->registerPublicAction('denied');
	}

	/**
	 * Override preDispatch method to perform permissions check and will forward request if access is denied.
	 * @return void
	 */
	public function preDispatch()
	{

		$request = $this->getRequest();

		// Public methods should not be restricted
		if($this->isPublicAction($request->getParam('action')))
			return;

		$permission = strtolower($request->getParam('module') . self::PermissionSeparator . $request->getParam('controller') . self::PermissionSeparator . $request->getParam('action'));

		// User has the required permission
		if(\Permission\Permission::capable($permission))
			return;
		
		// Normal request - forward to controller action response
		if(in_array(self::detect($request), [ self::$ResponseFormatPlain, self::$ResponseFormatUnknown ]))
			$this->forward('denied', [ 'request' => $request ], $request->getController(), $request->getModule());

		$request->setQuery('callback', 'alert');

		$this->responseJsonCallback('Access denied.');

		exit;
	}

	/**
	 * Common action for access denied. It may render subscription order page.
	 * @return void
	 */
	public function deniedAction()
	{
		if(\Install\Modules::isInstalled('subscription'))
			return $this->redirect($this->url([ 'controller' => 'subscription' ], 'subscription'));

		$this->render('denied', [ 'request' => $this->getRequest() ], [ 'module' => 'Base', 'controller' => 'Permission' ]);
	}

	/**
	 * Dynamically registers new public action method for this controller (calling controller)
	 * @param  string $action The action name without its postfix
	 * @return void           
	 */
	public function registerPublicAction($action)
	{
		if(!method_exists($this, $action . 'Action'))
			return;

		if(in_array($action . 'Action', $this->_public_actions))
			return;

		$this->_public_actions[$action] = $action . 'Action';
	}

	/**
	 * Dynamically unregisters a public action method from this controller (calling controller)
	 * @param  string $action The action name without its postfix
	 * @return void           
	 */
	public function unregisterPublicAction($action)
	{
		if(!array_key_exists($action, $this->_public_actions))
			return;

		unset($this->_public_actions[$action]);
	}

	/**
	 * Performs a check if the given action is public
	 * @param  string  $action The action to check for, without 'Action' postfix
	 * @return bool            True if it is a public action, false otherwise
	 */
	public function isPublicAction($action)
	{
		return array_key_exists($action, $this->_public_actions) && in_array($action . 'Action', $this->_public_actions);
	}
}