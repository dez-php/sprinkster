<?php

namespace Tag\Install;

use Tag\TagLetter;

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

		if(!$this->existRecord('menu', array('guid'=>'3927c0e8-22f6-3d89-b513-dc040c79e87'))) {
			$parent_id = $this->getRecordId('menu', array('guid' => '79a71c82-25b3-da32-c2b8-dc040c79e87'));
			$this->appendMenu(array(
					'widget' => 'tag',
					'config' => '',
					'is_group' => 0,
					'is_widget' => 0,
					'title' => 'Manage Tags',
					'route' => '',
					'group_id' => 'AdminMenu',
					'status' => 1,
					'parent_id' => $parent_id,
					'module' => 'tag',
					'guid' => '3927c0e8-22f6-3d89-b513-dc040c79e87'
			));
		}
		
		if(!$this->existRecord('menu', array('guid'=>'4f89220f-7a87-2c93-2bdf-dc040c79e87'))) {
			$this->appendMenu(array(
					'widget' => 'tag.widget.tags',
					'config' => '',
					'is_group' => 0,
					'is_widget' => 1,
					'title' => 'Pin tags',
					'route' => '',
					'group_id' => 'PinViewMiddle',
					'status' => 1,
					'parent_id' => null,
					'module' => 'tag',
					'guid' => '4f89220f-7a87-2c93-2bdf-dc040c79e87'
			));
		}
		if(!$this->existRecord('menu', array('guid' => '04e5f4ec-88c2-4af7-798b-dc040c79e87'))) {
            $parent_id = null;$title = 'Pins by tags';
            if(\Core\Registry::get('system_type') == 'pintastic') {
                $parent_id = $this->getRecordId('menu', array('guid' => '45d6dc67-0da2-bede-2251-dc040c79e87'));
            } elseif(\Core\Registry::get('system_type') == 'getsy2') {
                $parent_id = $this->getRecordId('menu', array('guid' => '7f862ca7-7092-2512-5ec4-dc040c79e87'));
                $title = 'Items by tags';
            }
			if($parent_id) {
                $this->insertAfterMenu(['39ba72df-3f14-f82b-847e-dc040c79e87'], array(
                    'sort_order' => 6,
                    'widget' => 'tag',
                    'config' => '',
                    'is_group' => 0,
                    'is_widget' => 0,
                    'title' => $title,
                    'route' => '',
                    'group_id' => 'FeatureMenu',
                    'status' => 1,
                    'parent_id' => $parent_id,
                    'module' => 'tag',
                    'guid' => '04e5f4ec-88c2-4af7-798b-dc040c79e87'
                ));
            }
		}
		if(!$this->existRecord('menu', array('guid'=>'b9ef2cd5-493e-d461-6651-dc040c79e87'))) {
			$this->execute('menu', array(
					'sort_order' => 4,
					'widget' => 'search_tag',
					'config' => '',
					'is_group' => 0,
					'is_widget' => 0,
					'title' => 'Tags',
					'route' => '',
					'group_id' => 'SearchMenu',
					'status' => 1,
					'parent_id' => null,
					'module' => 'tag',
					'guid'=>'b9ef2cd5-493e-d461-6651-dc040c79e87'
			));
		}
		if(!$this->existRecord('menu', array('guid'=>'11645936-ece7-9789-34d3-dc040c79e87'))) {
			$this->appendMenu(array(
					'sort_order' => 1,
					'widget' => 'tag.widget.related',
					'config' => '',
					'is_group' => 0,
					'is_widget' => 1,
					'title' => 'Related Items / Products',
					'route' => '',
					'group_id' => 'PinViewAfter',
					'status' => 1,
					'parent_id' => null,
					'module' => 'tag',
					'disabled' => 'return !isset($data->pin_tags_related_total) || !(int)$data->pin_tags_related_total;',
					'guid' => '11645936-ece7-9789-34d3-dc040c79e87'
			));
		}
		//form
		if(!$this->existRecord('form_extend', array('form_name'=>'pinForm', 'form'=>'tag.widget.pinform'))) {
			$this->execute('form_extend', array(
				'form_name' => 'pinForm',
				'save' => '\Tag\Helper\Pin',
				'form' => 'tag.widget.pinform',
				'validator' => '',
				'sort_order' => -999999,
				'status' => 1		
			));
		}

		if (!$this->existRecord('extend', array('extend' => '\tag\helper\pinOrder'))) {
			$this->execute('extend', array(
				'type' => 'order',
				'module' => 'Pin\getAll',
				'extend' => '\tag\helper\pinOrder',
				'sort_order' => 1,
				'status' => 1
			));
		}
		
		$ccTable = new \Tag\PinTag();
		$ccTable->createFK('pin_id', 'pin', 'id', 'CASCADE');
		$ccTable->createFK('tag_id', 'tag', 'id', 'CASCADE');
		
		$tt = new \Tag\Tag();
		$tt->createFK('letter_id', 'tag_letter', 'id', 'CASCADE');
		
		foreach(array_merge(range('A', 'Z'), [9]) AS $letter) {
			TagLetter::getByTag($letter . '', 1);
		}
	}
	
	public function uninstall() {
		$this->deleteRecord('menu', array('module' => 'tag'));
		$this->deleteRecord('form_extend', array('form_name'=>'pinForm', 'form'=>'tag.widget.pinform'));
		$this->deleteRecord('extend', array('extend' => '\tag\helper\Related'));
		$this->deleteRecord('extend', array('extend' => '\tag\helper\pinOrder'));
	}
	
	public function delete() {
		
		$ccTable = new \Tag\PinTag();
		$ccTable->deleteFK('pin_id', 'pin', 'id');
		$ccTable->deleteFK('tag_id', 'tag', 'id');
		
		$tt = new \Tag\Tag();
		$tt->deleteFK('letter_id', 'tag_letter', 'id');
		
		$this->query('DROP TABLE IF EXISTS `pin_tag`');
		$this->query('DROP TABLE IF EXISTS `tag`');
		$this->query('DROP TABLE IF EXISTS `tag_letter`');
	}
}