<?php
namespace Translate;

use \Core\Base\MemcachedManager;

class Translate {

	public static function getByNamespace($namespace) {
		$cache_key = MemcachedManager::key(__CLASS__, __METHOD__, $namespace);

		return MemcachedManager::get($cache_key, function() use ($namespace) {
			$db = \Core\Db\Init::getDefaultAdapter();
			$sql = $db->select()
						->from('translate', 'message')
						->where('namespace = ?', $namespace)
						->joinLeft('translate_data', 'translate.id = translate_data.translate_id AND translate_data.language_id = ' . \Core\Base\Action::getModule('Language')->getLanguageId(), 'IF(translate_data.message != "",translate_data.message,translate.message) AS message_translate');
			return $db->fetchPairs($sql, array());
		});
	}

	public static function getAll() {
		$db = \Core\Db\Init::getDefaultAdapter();
		$sql = $db->select()
					->from('translate', ['message', 'namespace'])
					->joinLeft('translate_data', 'translate.id = translate_data.translate_id AND translate_data.language_id = ' . \Core\Base\Action::getModule('Language')->getLanguageId(), 'IF(translate_data.message != "",translate_data.message,translate.message) AS message_translate');
		return $db->fetchAll($sql);
	}

	public static function insert($data) {
		$db = \Core\Db\Init::getDefaultAdapter();
		$db->insert('translate', $data);
		return $db->lastInsertId();
	}

	////////////////////// sys
	public static function getGroups() {
		$cache_key = MemcachedManager::key(__CLASS__, __METHOD__);

		return MemcachedManager::get($cache_key, function() {
			$db = \Core\Db\Init::getDefaultAdapter();
			$sql = $db->select()
						->from('translate', array('SUBSTRING_INDEX(`namespace`,"\\\",1) AS namespace','SUBSTRING_INDEX(`namespace`,"\\\",1) AS namespace'))
						->order('namespace ASC')
						->group('SUBSTRING_INDEX(`namespace`,"\\\",1)');
			return $db->fetchPairs($sql);
		});
	}
	
	public static function getGroup($group) {
		$cache_key = MemcachedManager::key(__CLASS__, __METHOD__, $group);

		return MemcachedManager::get($cache_key, function() use($group) {
			$db = \Core\Db\Init::getDefaultAdapter();
			$rows = array('namespace'); 
			$languages = \Core\Base\Action::getModule('Language')->getLanguages();
			foreach($languages AS $language) {
				$rows['percents_'.$language->id] = new \Core\Db\Expr('( ((SELECT COUNT(id) FROM translate_data WHERE translate_id IN ('.$db->select()->from(array('t_' . $language->id => 'translate'),'id')->where('t_' . $language->id . '.namespace = translate.namespace').') AND language_id = ' . $db->quote($language->id) . ') / COUNT(translate.id) )*100 )');
			}
			$sql = $db->select()
						->from('translate', $rows)
						->where('namespace LIKE ?',$group.'\\\%')
						->order('namespace ASC')
						->group('namespace');
			
			return $db->fetchAll($sql);
		});
	}
	
	public static function getGroupData($group) {
		$cache_key = MemcachedManager::key(__CLASS__, __METHOD__, $group);

		return MemcachedManager::get($cache_key, function() use ($group) {
			$db = \Core\Db\Init::getDefaultAdapter();
			$rows = array('*'); 
			$languages = \Core\Base\Action::getModule('Language')->getLanguages();
			foreach($languages AS $language) {
				$rows['text_'.$language->id] = new \Core\Db\Expr('(SELECT message FROM translate_data WHERE translate_id = translate.id AND language_id = ' . $db->quote($language->id) . ' LIMIT 1)');
			}
			$sql = $db->select()
						->from('translate', $rows)
						->where('namespace = ?',$group)
						->order('translate.message ASC');
			return $db->fetchAll($sql);
		});
	}

    public static function getGroupDataSearch($group, $search) {
        $cache_key = MemcachedManager::key(__CLASS__, __METHOD__, $group);

        return MemcachedManager::get($cache_key, function() use ($group, $search) {
            if(!$search || !trim($search))
                return [];
            $db = \Core\Db\Init::getDefaultAdapter();
            $rows = array('*');
            $languages = \Core\Base\Action::getModule('Language')->getLanguages();
            foreach($languages AS $language) {
                $rows['text_'.$language->id] = new \Core\Db\Expr('(SELECT message FROM translate_data WHERE translate_id = translate.id AND language_id = ' . $db->quote($language->id) . ' LIMIT 1)');
            }
            $sql = $db->select()
                ->from('translate', $rows)
                ->where('namespace LIKE ?',$group.'\\\%')
                ->where('translate.message LIKE ?',$search.'%')
                ->order('translate.message ASC');

            return $db->fetchAll($sql);
        });
    }
	
}