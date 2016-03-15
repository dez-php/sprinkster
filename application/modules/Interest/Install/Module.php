<?php

namespace Interest\Install;

class Module extends \Base\Install\Module {
	
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

		// Admin menu
		if (!$this->existRecord('menu', array('guid' => '27f7c72e-fe3c-6b34-c495-dc040c79e87'))) {
			$parent_id = $this->getRecordId('menu', array('guid' => '79a71c82-25b3-da32-c2b8-dc040c79e87'));
			$this->insertAfterMenu(['a42c356e-fc1a-dd1e-6c4d-dc040c79e87'], array(
				'widget' => 'interest',
				'config' => '',
				'is_group' => 0,
				'is_widget' => 0,
				'title' => 'Pin Interests',
				'route' => '',
				'group_id' => 'AdminMenu',
				'status' => 1,
				'parent_id' => $parent_id,
				'module' => 'interest',
				'guid' => '27f7c72e-fe3c-6b34-c495-dc040c79e87'
			));
		}
		// Category Group menu
		if (!$this->existRecord('menu', array('guid' => '14f36c84-5a07-429a-9a29-dc040c79e87'))) {
			$this->prependMenu(array(
				'widget' => 'interest.widget.balloons',
				'config' => '',
				'is_group' => 0,
				'is_widget' => 1,
				'title' => 'Pin Interests',
				'route' => '',
				'group_id' => 'CategoryGroup',
				'status' => 1,
				'parent_id' => null,
				'module' => 'interest',
				'guid' => '14f36c84-5a07-429a-9a29-dc040c79e87'
			));
		}
		
		if(!$this->existRecord('menu', array('guid' => 'f6647dc1-b452-d8c5-2e62-dc040c79e87'))) {
			$this->appendMenu(array(
				'widget' => 'interest.widget.follow',
				'config' => '',
				'is_group' => 0,
				'is_widget' => 1,
				'title' => 'Interest',
				'route' => '',
				'group_id' => 'UserFollowingMenu',
				'status' => 1,
				'parent_id' => null,
				'module' => 'interest',
				'guid' => 'f6647dc1-b452-d8c5-2e62-dc040c79e87'
			));
		}
		
		if(!$this->existRecord('event', array('class'=>'\Interest\Event\SetIndexes'))) {
			$this->execute('event', array(
				'namespace' => 'interest.insert.update',
				'class' => '\Interest\Event\SetIndexes',
				'method' => 'Indexes'	
			));
		}
		
		if(!$this->existRecord('event', array('class'=>'\Interest\Event\Pinit'))) {
			$this->execute('event', array(
				'namespace' => 'pin.insert.update',
				'class' => '\Interest\Event\Pinit',
				'method' => 'SetIndexes'	
			));
		}
		
		$table = new \Interest\Interest();
		$table->createFK('category_id', 'category', 'id', 'CASCADE');
		
		$table = new \Interest\InterestRelated();
		$table->createFK('interest_id', 'interest', 'id', 'CASCADE');
		$table->createFK('related_id', 'interest', 'id', 'CASCADE');
		
		$table = new \Interest\InterestPin();
		$table->createFK('interest_id', 'interest', 'id', 'CASCADE');
		$table->createFK('pin_id', 'pin', 'id', 'CASCADE');

		$table = new \Interest\InterestTag();
		$table->createFK('interest_id', 'interest', 'id', 'CASCADE');
		
	}
	
	public function uninstall() {
		$this->deleteRecord('menu', array('module' => 'interest'));
		$this->deleteRecord('event', array('class'=>'\Interest\Event\SetIndexes'));
		$this->deleteRecord('event', array('class'=>'\Interest\Event\Pinit'));
	}
	
	public function delete() {
		
		$table = new \Interest\Interest();
		$table->deleteFK('category_id', 'category', 'id');
		
		$table = new \Interest\InterestRelated();
		$table->deleteFK('interest_id', 'interest', 'id');
		$table->deleteFK('related_id', 'interest', 'id');
		
		$table = new \Interest\InterestPin();
		$table->deleteFK('interest_id', 'interest', 'id');
		$table->deleteFK('pin_id', 'pin', 'id');
		
		$table = new \Interest\InterestTag();
		$table->deleteFK('interest_id', 'interest', 'id');

		$this->query('DROP TABLE IF EXISTS `interest_follow`');
		$this->query('DROP TABLE IF EXISTS `interest_pin`');
		$this->query('DROP TABLE IF EXISTS `interest_related`');
		$this->query('DROP TABLE IF EXISTS `interest_tag`');
		$this->query('DROP TABLE IF EXISTS `interest`');
	}
	
}