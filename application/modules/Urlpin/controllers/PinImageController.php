<?php

namespace Urlpin;

class PinImageController extends \Base\PermissionController {
	
	public $error = [];
	
	public function init()
	{
		set_time_limit(0);
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction()
	{
		$request = $this->getRequest();

		if(!\User\User::getUserData()->id)
			$this->redirect($this->url([ 'controller' => 'login' ], 'user_c'));

		$return = [];
		$data = [];
		
		$url = urldecode(trim($request->getRequest('url')));

		if(!preg_match('~^https?://~i', $url))
			$url = 'http://' . $url;

		$cleared = NULL;
		$images = false;

		if(!\Core\Form\Validator::clearHost($request->getRequest('url'))) {
			$this->error['url'] = $this->_('Please enter a valid URL');
			goto render;
		}
			

		$cache = $this->getUrlCache();
		$cache_id = md5(trim($url, '/'));
		$set = FALSE;
		
		$db = \Core\Db\Init::getDefaultAdapter();
		$db->closeConnection();
		
		if(FALSE === ($images = $cache->get($cache_id)))
		{
			$helperImage = new \Urlpin\Helper\Element($url);
			$images = $helperImage->getAdapter()->result();
			
			if($images['images']) {
				$cache->set($cache_id, $images);
			} else {
				$return['errors']['http'] = sprintf($this->_('Data from %s is empty!'), $url);
			}
		}
		
		if(isset($images['images']) && $images['images'])
		{
			foreach($images['images'] AS &$img)
			{
				if(!$img['title'])
					$img['title'] = $images['title'];

				$img['title'] = \Core\Utf8::splitText($img['title'], 255);
				$img['create'] = $this->url([
						'controller' => 'create-pin',
						'query' => '?media=' . urlencode($img['image'])
							. '&title=' . urlencode($images['title'])
							. '&description=' . urlencode($img['title'])
							. '&url=' . urlencode(isset($images['url']) ? $img['url'] : $url)
							. '&price=' . urlencode( isset($img['price']) ? $img['price'] : 0)
					],
					'urlpin_c',
					FALSE,
					FALSE
				);
			}

		}
		else
		{
			$this->error['http'] = sprintf($this->_('Data from %s is empty!'), $url);
		}
		
		render:
		
		$db->getConnection();
		
		$this->render('index', ['images' => $images, 'url' => $url]);
	}
	
	/**
	 * @param string $url
	 * @return \Core\Cache\Frontend\String
	 */
	private function getUrlCache() {
		$frontendOptionsCore = array(
				'lifetime' => 300,
		);
		 
		$frontendOptionsPage = array(
				'lifetime' => 300
		);
		
		if(!file_exists(BASE_PATH . '/cache/pinurl/')) {
			mkdir(BASE_PATH . '/cache/pinurl/', 0777, true);
		}
		
		$backendOptions  = array('cache_dir' => BASE_PATH . '/cache/pinurl/');
		$cache = \Core\Cache\Base::factory('String', 'File', $frontendOptionsCore, $backendOptions);
		$cache->clean(\Core\Cache\Base::CLEANING_MODE_OLD);
		return $cache;
	}
	
}