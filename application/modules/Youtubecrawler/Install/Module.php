<?php

namespace Youtubecrawler\Install;

class Module extends \Base\Install\Module {

	protected $system_reference = array(
		array('object' => 'User\\User','name' => 'YoutubecrawlerUser','columns' => 'id','refTableClass' => 'Youtubecrawler\\Search','refColumns' => 'user_id','where' => '','singleRow' => '0','parent_id' => NULL,'module' => 'youtubecrawler'),
		array('object' => 'Pin\\Pin','name' => 'YoutubecrawlerUser','columns' => 'id','refTableClass' => 'Youtubecrawler\\Link','refColumns' => 'pin_id','where' => '','singleRow' => '0','parent_id' => NULL,'module' => 'youtubecrawler'),
		array('object' => 'Category\\Category','name' => 'YoutubecrawlerSearchCategory','columns' => 'id','refTableClass' => 'Youtubecrawler\\Search','refColumns' => 'category_id','where' => '','singleRow' => '0','parent_id' => NULL,'module' => 'youtubecrawler')
	);
	
	public function install() {
		$sql = file_get_contents(__DIR__.'/sql.sql');
		$parser = new \Core\Db\Schema\Parser();
		$queries = $parser->delta( $sql, false, false);
		if(isset($queries['dbh_global'])) {
			foreach ($queries['dbh_global'] AS $table => $queris) {
				foreach($queris AS $q) {
					$this->query($q['query']);
				}
			}
		}
		
		//top menu
		if(!$this->existRecord('menu', array('guid' => 'b08ac89f-5b1a-46cc-5a84-dc040c79e'))) {
			$parent_id = $this->getRecordId('menu', array('guid' => 'a98557bf-33e6-3c6d-3bd9-dc040c79e87'));
			$this->appendMenu(array(
				'widget' => '',
				'config' => '',
				'is_group' => 0,
				'is_widget' => 0,
				'title' => 'Crawlers',
				'route' => '',
				'group_id' => 'AdminMenu',
				'status' => 1,
				'parent_id' => $parent_id,
				'module' => 'admin',
				'guid' => 'b08ac89f-5b1a-46cc-5a84-dc040c79e'
			));
		}
		
		//poweruser menu
		if(!$this->existRecord('menu', array('guid' => '35ba48fa-b7d4-eafb-cacb-dc040c79e87'))) {
			$parent_id = $this->getRecordId('menu', array('guid' => 'b08ac89f-5b1a-46cc-5a84-dc040c79e'));
			$this->execute('menu', array(
				'sort_order' => ((int)$this->getMax('sort_order', 'menu', array('parent_id'=>$parent_id)) + 1),
				'widget' => 'youtube_crawler',
				'config' => '',
				'is_group' => 0,
				'is_widget' => 0,
				'title' => 'Youtube crawler',
				'route' => '',
				'group_id' => 'AdminMenu',
				'status' => 1,
				'parent_id' => $parent_id,
				'module' => 'youtubecrawler',
				'guid' => '35ba48fa-b7d4-eafb-cacb-dc040c79e87'	
			));
		}
		
		$this->installIfNotExist('crons', array(
				'minute' => '2',
				'command' => '*/2 * * * *',
				'route' => 'youtubecrawler',
				'module' => 'youtube_crawler',
				'controller' => 'cron',
				'action' => ''
		));
		
		$this->installIfNotExist('crons', array(
				'minute' => '1',
				'command' => '* * * * *',
				'route' => 'youtubecrawler',
				'module' => 'youtube_crawler',
				'controller' => 'cron',
				'action' => 'links'
		));
		
		
		$tt = new \Youtubecrawler\Link();
		$tt->createFK('youtube_search_id', 'crawler_youtube_search', 'id', 'CASCADE');
		$tt->createFK('user_id', 'user', 'id', 'CASCADE');
		$tt->createFK('pin_id', 'pin', 'id', 'CASCADE');
		$tt->createFK('category_id', 'category', 'id', 'CASCADE');
		
		$tt = new \Youtubecrawler\Search();
		$tt->createFK('user_id', 'user', 'id', 'CASCADE');
		$tt->createFK('category_id', 'category', 'id', 'CASCADE');
		
	}
	
	public function uninstall() {
		$this->deleteRecord('menu', array('module' => 'youtubecrawler'));
		$this->deleteRecord('crons', array('module' => 'youtube_crawler'));

		//delete Crawlers menu if no childs
		$this->removeMenuIfNoChilds('b08ac89f-5b1a-46cc-5a84-dc040c79e');
	}
	
	public function delete() {
		$tt = new \Youtubecrawler\Link();
		$tt->deleteFK('youtube_search_id', 'crawler_youtube_search', 'id');
		$tt->deleteFK('user_id', 'user', 'id');
		$tt->deleteFK('pin_id', 'pin', 'id');
		$tt->deleteFK('category_id', 'category', 'id');
		
		$tt = new \Youtubecrawler\Search();
		$tt->deleteFK('user_id', 'user', 'id');
		$tt->deleteFK('category_id', 'category', 'id');
		
		$this->query('DROP TABLE IF EXISTS `crawler_youtube_links`');
		$this->query('DROP TABLE IF EXISTS `crawler_youtube_search`');
	}
}