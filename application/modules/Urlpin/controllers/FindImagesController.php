<?php

namespace Urlpin;

class FindImagesController extends \Base\PermissionController {
	
	public function init()
	{
		set_time_limit(0);
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction()
	{
		$request = $this->getRequest();

		if( !$request->isXmlHttpRequest() )
			$this->redirect($this->url([], 'welcome_home'));

		if(!\User\User::getUserData()->id)
			return $this->responseJsonCallback([ $this->url([ 'controller' => 'login' ], 'user_c') ]);

		$return = [];
		$data = [];
		
		$url = trim($request->getRequest('url'));

		if(!preg_match('~^https?://~i', $url))
			$url = 'http://' . $url;

		$cleared = NULL;

		if(!\Core\Form\Validator::clearHost($request->getRequest('url')))
			return $this->responseJsonCallback([ 'errors' => [ 'url' => $this->_('Please enter a valid URL') ]]);

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
// 				$images['title'] = \Core\Utf8::convert($images['title'], $images['charset'], 'utf-8');
// 				foreach($images['images'] AS &$image)
// 					$image['title'] = \Core\Utf8::convert($image['title'], $images['charset'], 'utf-8');
				$cache->set($cache_id, $images);
			} else {
				$return['errors']['http'] = sprintf($this->_('Data from %s is empty!'), $url);
			}
		}
		
		if(isset($images['images']) && $images['images'])
		{
			
			$data['location'] = $this->url([ 'controller' => 'pin-image', 'query' => '?url=' . urlencode($url) ], 'urlpin_c', false, false);
			
			return $this->responseJsonCallback($data);
		}
		else
		{
			$return['errors']['http'] = sprintf($this->_('Data from %s is empty!'), $url);
		}
		
		$db->getConnection();
		
		$this->responseJsonCallback($return);
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