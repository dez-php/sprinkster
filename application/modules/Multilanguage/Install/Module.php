<?php

namespace Multilanguage\Install;

class Module extends \Base\Install\Module {
	
	private $config = [
		['group' => 'config','key' => 'language_id','value' => '0','serialize' => '0','form_label' => 'Default language','form_type' => 'Callback\\Multilanguage\\Helper\\Select','form_list' => '','form_required' => '1','sort_order' => '6','form_helpMessage' => '']
	];
	
	public function install() { 
		foreach($this->config AS $c) {
            if (!$this->existRecord('config', 'key', $c['key'])) {
                if ($c['key'] == 'language_id')
                    $c['value'] = \Core\Base\Action::getModule('Language')->getLanguageId();
                $this->execute('config', $c);
            }
        }

		$locale_id = $this->getRecordId('menu', ['guid'=>'8a407071-b6b6-c879-cd96-dc040c79e87']);
		
		//Admin menu
		if(!$this->existRecord('menu', ['guid'=>'89b6525a-317c-df1f-90a5-dc040c79e87'])) {
			$this->appendMenu([
					'widget' => 'multilanguage',
					'config' => '',
					'is_group' => 0,
					'is_widget' => 0,
					'title' => 'Manage Languages',
					'route' => '',
					'group_id' => 'AdminMenu',
					'status' => 1,
					'parent_id' => $locale_id,
					'module' => 'multilanguage',
					'guid' => '89b6525a-317c-df1f-90a5-dc040c79e87'
			]);
		}
		
		//Front menu
		if(!$this->existRecord('menu', ['guid'=>'2d29f756-1e60-fc68-4cbf-dc040c79e87'])) {
			$this->prependMenu([
					'widget' => 'multilanguage.widget.menu',
					'config' => '',
					'is_group' => 0,
					'is_widget' => 1,
					'title' => 'Language',
					'route' => '',
					'group_id' => 'FooterMenu',
					'status' => 1,
					'parent_id' => null,
					'module' => 'multilanguage',
					'guid' => '89b6525a-317c-df1f-90a5-dc040c79e87'
			]);
		}
	}
	
	public function uninstall() {
		foreach($this->config AS $c) {
			if($this->existRecord('config', 'key', $c['key'])) {
				$this->deleteRecord('config', ['key' => $c['key']]);
			}
		}
		$this->deleteRecord('menu', array('module' => 'multilanguage'));
	}
	
	public function delete() {
		$language_id = 1;
		$this->deleteRecord('translate_data', array('language_id' => '!=' . $language_id));
		
		$languages = \Core\Base\Action::getModule('Language')->getLanguages();
		$languageTable = new \Language\Language();
		foreach($languages AS $language) {
			if($language->id != $language_id) {
				$languageTable->delete(['id = ?' => $language->id]);
			}
		}
	}
}