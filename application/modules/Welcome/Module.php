<?php

namespace Welcome;

class Module extends \Core\Base\Module
{

	protected static $allowed = [
		'route' => ['invite\request', 'paymentgateway\payment'],
		'module' => [ 'welcome', 'cron', 'i18' ],
		'controller' => [ 'activate', 'reset-password', 'forgotten', 'status', 'login','register','forward','cron', 'api' ],
		'widget' => []
	];
	
	public function registerEvent( \Core\Base\Event $e, $application ) {
		$self = $this;
		$botDetect = new \Core\Bot\Detect();
		if($botDetect->isBot() || $this->getRequest()->isXmlHttpRequest() || !\Base\Config::get('welcome_status'))
			return;
		$e->register('onBeforeDispatch', [ $this, 'onBeforeDispatch' ]);
	}

    public function onBeforeDispatch()
    {
        $request = $this->getRequest();
        if($request->getParam('___layout___') == 'admin')
            return;

        $user_data = \User\User::getUserData();

        if($user_data->id)
        {
            if($request->getQuery('widget'))
                return;
            $front = $this->getApplication()->getRouter();
        
            if($front->getCurrentRouteName() == 'default' && \Base\Config::get('welcome_inner_pages'))
            {
                $action = \Core\Base\Action::getInstance();
                $action->redirect( $action->url(array(),'welcome_home') );
            }
        } else if(!$user_data->id && $request->getModule() != 'welcome') {
            if($this->allowed($request))
                return;
            $front = $this->getApplication()->getRouter();

            if(($front->getCurrentRouteName() == 'default' && \Base\Config::get('welcome_inner_pages')) || !\Base\Config::get('welcome_inner_pages')) {
                $action = \Core\Base\Action::getInstance();
                $action->forward('index', null, 'index', 'welcome');
            }
        }
    }
    
    ///////////////////
    
    public function allowed(\Core\Http\Request $request) {
    	if($request->isFacebookBot())
    		return true;

		$forwarded = $request->getForwarded();
    	foreach(self::$allowed AS $k => $data) {
    		if($k == 'module') {
    			if(in_array(strtolower($forwarded ? $forwarded['module'] : $request->getModule()), $data)) {
    				return true;
    			}
    		} elseif($k == 'controller') {
    			if(in_array(strtolower($forwarded ? $forwarded['controller'] : $request->getController()), $data)) {
    				return true;
    			}
    		} elseif($k == 'route') {
    			if(in_array(strtolower($forwarded ? ($forwarded['module'] . NS . $forwarded['controller']) : ($request->getModule() . NS . $request->getController())), $data)) {
    				return true;
    			}
    		} elseif($k == 'widget') {
    			if(in_array(strtolower($request->getQuery('widget')), $data)) {
    				return true;
    			}
    		}
    	}
    	return false;
    }
    
    public static function setAllowed(array $allowed = null) {
    	if($allowed) {
    		self::$allowed = \Core\Utf8::array_change_case_unicode(array_merge_recursive(self::$allowed, $allowed));
    	}
    }

    public function addScript() {
    	$dir = $this->getComponent('Alias')->get(__NAMESPACE__) . '/asset/';
		$document = $this->getComponent('document');
		$asset = $this->getComponent('AssetManager');
		$asset->publish($dir);
		$document->addScriptFile($asset->getPublishedUrl($dir) . '/js/welcome.main.js');
    }
}