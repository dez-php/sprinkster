<?php

namespace S3\Install;

class Module extends \Base\Install\Module {
	
	protected $config = array(
		array('group' => 's3','key' => 's3_access_key','value' => NULL,'serialize' => '0','form_label' => 'Access key','form_type' => 'Text','form_list' => '','form_required' => '1','sort_order' => '2','form_helpMessage' => ''),
		array('group' => 's3','key' => 's3_secret_key','value' => NULL,'serialize' => '0','form_label' => 'Secret key','form_type' => 'Text','form_list' => '','form_required' => '1','sort_order' => '1','form_helpMessage' => ''),
		array('group' => 's3','key' => 's3_bucklet','value' => NULL,'serialize' => '0','form_label' => 'Bucket','form_type' => 'Text','form_list' => '','form_required' => '1','sort_order' => '3','form_helpMessage' => ''),
		array('group' => 's3','key' => 's3_bucklet_location','value' => NULL,'serialize' => '0','form_label' => 'Bucket location','form_type' => 'Url','form_list' => '','form_required' => '1','sort_order' => '4','form_helpMessage' => 'The default bucket location is https://s3.amazonaws.com/bucketname/'),
		array('group' => 's3','key' => 's3_ssl','value' => '0','serialize' => '0','form_label' => 'SSL support','form_type' => 'Single','form_list' => 'a:2:{i:1;s:3:"Yes";i:0;s:2:"No";}','form_required' => '0','sort_order' => '5','form_helpMessage' => ''),
		array('group' => 's3','key' => 's3_status','value' => '1','serialize' => '0','form_label' => 'Module status','form_type' => 'Single','form_list' => 'a:2:{i:1;s:6:"Active";i:0;s:8:"Inactive";}','form_required' => '1','sort_order' => '0','form_helpMessage' => 'to create Amazon S3 account, go to http://aws.amazon.com/s3/')
	); 

	public function install() {
// 		$sql = file_get_contents(__DIR__.'/sql.sql');
// 		$parser = new \Core\Db\Schema\Parser();
// 		$queries = $parser->delta( $sql, false, false);
		
// 		if(isset($queries['dbh_global'])) {
// 			foreach ($queries['dbh_global'] AS $table => $queris) {
// 				foreach($queris AS $q) {
// 					$this->query($q['query']);
// 				}
// 			}
// 		}
		
		
		
		foreach($this->config AS $c) {
			if(!$this->existRecord('config', 'key', $c['key'])) {
				$this->execute('config', $c);
			}
		}
		
		// Admin
		if(!$this->existRecord('menu', array('guid' => 'b9f70ca3-95fb-1a8c-3b66-dc040c79e87'))) {
			$parent_id = $this->getRecordId('menu', array('guid' => 'a98557bf-33e6-3c6d-3bd9-dc040c79e87'));
			$this->appendMenu(array(
				'widget' => '',
				'config' => '',
				'is_group' => 0,
				'is_widget' => 0,
				'title' => 'Manage Upload',
				'route' => '',
				'group_id' => 'AdminMenu',
				'status' => 1,
				'parent_id' => $parent_id,
				'module' => 'admin',
				'guid' => 'b9f70ca3-95fb-1a8c-3b66-dc040c79e87'
			));
		}
		
		//poweruser menu
		if(!$this->existRecord('menu', array('guid' => '1c2ba635-c8ed-2148-c36f-dc040c79e87'))) {
			$parent_id = $this->getRecordId('menu', array('guid' => 'b9f70ca3-95fb-1a8c-3b66-dc040c79e87'));
			$this->execute('menu', array(
				'sort_order' => ((int)$this->getMax('sort_order', 'menu', array('parent_id'=>$parent_id)) + 1),
				'widget' => 's3',
				'config' => '',
				'is_group' => 0,
				'is_widget' => 0,
				'title' => 'Amazon S3',
				'route' => '',
				'group_id' => 'AdminMenu',
				'status' => 1,
				'parent_id' => $parent_id,
				'module' => 's3',
				'guid' => '1c2ba635-c8ed-2148-c36f-dc040c79e87'	
			));
		}
		
		if(!$this->existRecord('upload_method', array('module'=>'s3'))) {
			$this->execute('upload_method', array(
				'module' => 's3',
				'title' => 'Amazon S3 Storage',
				'sys' => 0
			));
		}
		
	}
	
	public function uninstall() {
		if(strtolower(\Base\Config::get('config_upload_method')) == 's3' ) {
			throw new \Core\Exception('This upload method is the default one! It cannot be uninstall!');
		} else {
			$this->deleteRecord('menu', array('module' => 's3'));
			$this->deleteRecord('upload_method', array('module'=>'S3'));
			
			$this->removeMenuIfNoChilds('b9f70ca3-95fb-1a8c-3b66-dc040c79e87');
		}
	}
	
	public function delete() {
		$this->deleteRecord('config', array('group'=>'s3'));
	}
}