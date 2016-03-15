<?php

namespace Aacache;

class Module extends \Core\Base\Module
{
	
	public function registerEvent( \Core\Base\Event $e, $application ) {
		$e->register('onBootstrap', [$this,'onBootstrap']);
	}
	
	public function onBootstrap() {
		$request = $this->getRequest();
			
		if(\Base\Config::get('cache_status_metadata') && \Base\Config::get('cache_type_metadata')) {
			if(!file_exists(BASE_PATH . '/cache/MetadataCache/') || !is_dir(BASE_PATH . '/cache/MetadataCache/')) {
				@mkdir(BASE_PATH . '/cache/MetadataCache/', 0777, true);
			}
			\Core\Base\Front::getInstance()->setParam('disableOutputBuffering', true);
			$frontendOptionsCore = array(
					'automatic_serialization' => true,
					'lifetime' => \Base\Config::get('cache_live_metadata')
			);
			$backendOptions  = array(
					'cache_dir' => BASE_PATH . '/cache/MetadataCache/',
					'servers' => array(
							'host' => \Base\Config::get('cache_host'),
							'port' => \Base\Config::get('cache_port'),
					)
			);
			$coreCache = \Core\Cache\Base::factory('Core', \Base\Config::get('cache_type_metadata')?\Base\Config::get('cache_type_metadata'):\Base\Config::get('cache_type'), $frontendOptionsCore, $backendOptions);
			\Core\Registry::set('coreCache', $coreCache);
			//clear old
			$coreCache->clean(\Core\Cache\Base::CLEANING_MODE_OLD);
			// Start running the DB/Core cache
			\Base\Model\Reference::setDefaultMetadataCache($coreCache);
		}
	
		//disable cache for admin
		if($request->getSegment(1) == 'admin')
			return;
			
		if(\Base\Config::get('cache_status') && \Base\Config::get('cache_type') && !$request->isXmlHttpRequest()) {
			if(!file_exists(BASE_PATH . '/cache/PageCache/') || !is_dir(BASE_PATH . '/cache/PageCache/')) {
				@mkdir(BASE_PATH . '/cache/PageCache/', 0777, true);
			}
			\Core\Base\Front::getInstance()->setParam('disableOutputBuffering', true);
			$frontendOptionsPage = array(
					'automatic_serialization' => true,
					'lifetime' => \Base\Config::get('cache_live'),
					'default_options' => array(
							'cache_with_get_variables' => true,
							'cache_with_post_variables' => true,
							'cache_with_session_variables' => true,
							'cache_with_files_variables' => true,
							'cache_with_cookie_variables' => true,
							'make_id_with_get_variables' => true,
							'make_id_with_post_variables' => true,
							'make_id_with_session_variables' => true,
							'make_id_with_files_variables' => true,
							'make_id_with_cookie_variables' => true,
					)
			);
			$backendOptions  = array('cache_dir' => BASE_PATH . '/cache/PageCache/');
			$pageCache = \Core\Cache\Base::factory('Page', \Base\Config::get('cache_type'), $frontendOptionsPage, $backendOptions);
			\Core\Registry::set('pageCache', $pageCache);
			//clear old
			$pageCache->clean(\Core\Cache\Base::CLEANING_MODE_OLD);
			$pageCache->start();
		}
	}

}
