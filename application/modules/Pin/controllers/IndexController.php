<?php
namespace Pin;

use \Core\Debug\Debug;

class IndexController extends \Base\PermissionController {
	
	public function init() {
		$request = $this->getRequest();

		if($request->isXmlHttpRequest())
			$this->noLayout(true);
		
	}
	
	public function indexAction() {
		
		$request = $this->getRequest();
		if($request->getParam('apis') == 'true')
			$this->forward('apis');
		
		$data = array(
			'isXmlHttpRequest' => $request->isXmlHttpRequest()
		);

		if($request->getQuery('nolayout') == 'true') {
			$this->noLayout(true);
		}
		
		$pin_id = $request->getRequest('pin_id');

		$pinTable = new \Pin\Pin();
		$pin = $pinTable->get($pin_id);
		
		if(!$pin) {
			$this->forward('error404');
		}

		// Retry Pin Colors Extraction
		if(!(new \Pin\PinToColor)->countByPinId($pin->id))
			\Core\Http\Thread::run('\Color\Event\SetColor::PinColors', $pin->id);
		
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
		
		//labels and other
		$ext = \Pin\Helper\Ext::parse($pin, false);
		foreach($ext AS $key => $dataExt) {
			if(isset($data[$key])) {
				$data[$key] = \Core\Arrays::array_merge($data[$key], $dataExt);
			} else {
				$data[$key] = $dataExt;
			}
		}

		//labels and other
		if(!$request->isXmlHttpRequest()) {
			//set page metatags
			$this->placeholder('meta_tags', $this->render('header_metas', array('pin' => $pin),null,true));
		}
		
		$data['pin'] = $pin;

		$self = \User\User::getUserData();
		if($self->id) {
			$likeTable = new \Pin\PinLike();
			$sql = $likeTable->select()->from($likeTable,'COUNT(1)')->where($likeTable->makeWhere(array(
				'pin_id' => $pin_id,
				'user_id' => $self->id	
			)))->useIndex(array('pin_id','user_id'));
			$data['isliked'] = $pinTable->getAdapter()->fetchOne($sql);//$likeTable->countByPinId_UserId($pin_id, $self->id);

			$repin = new \Pin\PinRepin;
	    	$wishlist = $repin->fetchRow([ 'user_id = ?' => $self->id, 'pin_id = ?' => $data['pin']->id]);
	    	if($wishlist)
	    		$data['wishlist'] = (new \Wishlist\Wishlist())->get($wishlist->wishlist_id);
		} else {
			$data['isliked'] = 0;
		}
		
		$data['popup'] = $request->isXmlHttpRequest();
		if($data['popup']) {
			$data['prev_pin'] = null;
			$data['next_pin'] = null;
		} else {
			$data['prev_pin'] = $pinTable->get($pin->id,'prev');
			$data['next_pin'] = $pinTable->get($pin->id,'next');
		}
		
		/////////////gallery
		$data['gallery'] = false;
		if($data['pin']['gallery']) {
			$pinGalleryTable = new \Pin\PinGallery();
			$data['gallery'] = $pinGalleryTable->fetchAll([ 'pin_id = ?' => $data['pin']->id ],'sort_order ASC');
		}
		
		//// update view
        \Pin\PinView::updateCounter($pin->id);
		//\Core\Http\Thread::run(['\Pin\PinView','updateCounter'], $pin->id);
		/////////////////////

		if (! preg_match ( '/^([a-z0-9_.]{1,})$/i', $request->getRequest ( 'callback' ) )) {		
            $this->render('index', $data);
		} else {
			exit;
			if($request->isXmlHttpRequest()) {
				$this->callback = true;
				$this->responseJsonCallback(array(
					'content' => $this->renderBuffer('index', $data, null, true),
					'title' => $pin->title,
					'url' => $this->url(['pin_id' => $pin_id,'query' => $this->urlQuery($pin->title)], 'pin')
				));
			} else {
				$this->render('index', $data);
			}
		}
		
	}
	
	public function apisAction() {
		$request = $this->getRequest();
		$pin_id = $request->getParam('pin_id');
		$callback = $request->getQuery('callback');
		$request->setQuery('callback', '');
		$pin = (new \Pin\Pin())->get($pin_id);
		
		if(!$pin)
			return $this->responseJsonCallback(['error' => $this->_('Record not found!')]);
		
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
		
		$json = [
			'id' => $pin->id,
			'title' => $pin->title,
			'url' => $this->url(['pin_id' => $pin->id,'query' => $this->urlQuery($pin->title)], 'pin'),
			'elements' => [
				'PinViewGallery' => [],
				'PinActions' => [],
				'PinButtons' => [],
				'PinViewMiddle' => [],
				'PinViewBottom' => [],
				'PinViewRight' => [],
				'PinViewAfter' => []
			]
		];
		
		//gallery
		if($pin->gallery) {
			$gallery = (string)$this->widget('pin.widget.gallery',['pin' => $pin]);
			if($gallery)
				$json['elements']['PinViewGallery'][] = $gallery;
		}

		//pin actions
		$actions = \Base\Menu::getMenu('PinActions');
		if($actions && count($actions)) {
			foreach ($actions AS $row => $widget) {
				if ($widget->is_widget) {
					$config = [];
					if ($widget->config)
						$config = unserialize($widget->config);
						
					$config['instance'] = $widget;
					$config['pin'] = $pin;
					$config['template'] = 'view';
					if(!$widget->disabled($pin, $config))
						$json['elements']['PinActions'][] = (string)$this->widget($widget->widget, $config);
				}
				else {
					$route = (array) unserialize($widget->route);
					$route['pin_id'] = $pin->id;
		
					$config = [];
					if ($widget->config)
						$config = unserialize($widget->config);
		
					if(!$widget->disabled($pin, $config))
						$json['elements']['PinActions'][] = \Core\Html::tag('li', [], \Core\Html::link($this->url($route, $widget->widget), $this->_($widget->title), $config));
		
				}
			}
		}
		
		//pin buttons
		$json['elements']['PinButtons'][] = (string)$this->widget('pin.widget.groupbuttons', ['pin' => $pin]);
		$actions_buttons = \Base\Menu::getMenu('PinButtons');
		if($actions_buttons && count($actions_buttons)) {
			foreach ($actions_buttons AS $row => $widget) {
				if ($widget->is_widget) {
					$config = [];
					if ($widget->config)
						$config = unserialize($widget->config);
						
					$config['instance'] = $widget;
					$config['pin'] = $pin;
					$config['template'] = 'view';
					if(!$widget->disabled($pin, $config))
						$json['elements']['PinButtons'][] = (string)$this->widget($widget->widget, $config);
				}
				else {
					$route = (array) unserialize($widget->route);
					$route['pin_id'] = $pin->id;
		
					$config = [];
		
					if ($widget->config)
						$config = unserialize($widget->config);
		
					if(!$widget->disabled($pin, $config))
						$json['elements']['PinButtons'][] = \Core\Html::tag('li', [], \Core\Html::link($this->url($route, $widget->widget), $this->_($widget->title), $config));
		
				}
			}
		}

        //pin middle
        $menu = \Base\Menu::getMenu('PinRibbon');
        if($menu)
        {
            foreach($menu AS $widget)
            {
                if($widget->is_widget)
                {
                    $config = [];
                    if($widget->config)
                        $config = unserialize($widget->config);

                    $config['instance'] = $widget;
                    $config['pin'] = $pin;
                    $config['module'] = $this->getRequest()->getModule();
                    $config['position'] = 'PinRibbon';
                    $config['template'] = 'view';
                    if(!$widget->disabled($pin, $config))
                        $json['elements']['PinRibbon'][] = (string)$this->widget($widget->widget, $config);
                }
            }
        }
		
		//pin middle
		$menu = \Base\Menu::getMenu('PinViewMiddle');
		if($menu)
		{
			foreach($menu AS $widget)
			{
				if($widget->is_widget)
				{
					$config = [];
					if($widget->config)
						$config = unserialize($widget->config);
						
					$config['instance'] = $widget;
					$config['pin'] = $pin;
					$config['module'] = $this->getRequest()->getModule();
					$config['position'] = 'pinMiddle';
					if(!$widget->disabled($pin, $config))
						$json['elements']['PinViewMiddle'][] = (string)$this->widget($widget->widget, $config);
				}
			}
		}
		
		//pin bottom
		$menu = \Base\Menu::getMenu('PinViewBottom');
		if($menu)
		{
			foreach($menu AS $widget)
			{
				if(!$widget->is_widget)
					continue;
		
				$config = [];
				if($widget->config)
					$config = unserialize($widget->config);
		
				$config['instance'] = $widget;
				$config['pin'] = $pin;
				$config['position'] = 'pinBottom';
				$config['module'] = $this->getRequest()->getModule();
				if(!$widget->disabled($pin, $config))
					$json['elements']['PinViewBottom'][] = (string)$this->widget($widget->widget, $config);
			}
		}
		
		// pin sidebar
		$menu = \Base\Menu::getMenu('PinViewRight');
		
		if($menu)
		{
			foreach($menu AS $widget)
			{
				if(!$widget->is_widget)
					continue;
		
				$config = array();
				if($widget->config)
					$config = unserialize($widget->config);
					
				$config['instance'] = $widget;
				$config['pin'] = $pin;
				$config['module'] = $this->getRequest()->getModule();
				$config['position'] = 'pinRight';
				if(!$widget->disabled($pin, $config))
					$json['elements']['PinViewRight'][] = (string)$this->widget($widget->widget, $config);
			}
		}
		
		//pin after
		$menu = \Base\Menu::getMenu('PinViewAfter');
		if($menu)
		{
			foreach($menu AS $k=> $widget)
			{
				if(!$widget->is_widget)
					continue;
		
				$config = array();
				if($widget->config)
					$config = unserialize($widget->config);
		
				$config['instance'] = $widget;
				$config['pin'] = $pin;
				$config['module'] = $this->getRequest()->getModule();
				$config['position'] = 'pinAfter';
				if(!$widget->disabled($pin, $config))
					$json['elements']['PinViewAfter'][] = (string)$this->widget($widget->widget, $config);
			}
		}
		$callback ? $request->setQuery('callback', $callback) : false;
		
		$this->responseJsonCallback($json);
	}
	
}