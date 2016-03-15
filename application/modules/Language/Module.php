<?php

namespace Language;

class Module extends \Core\Base\Module
{

	/**
	 * @var \Core\Db\Table\Row\AbstractRow
	 */
	private $language = null;
	/**
	 * @var array
	 */
	private $languages = array();
	
	/**
	 * @param null|number $language_id
	 * @throws \Core\Exception
	 * @return boolean
	 */
	public function setLanguage($language_id = null) { 
		if(!$this->languages) {
			$language = (new \Language\Language())->fetchRow(['status = 1']);
			if($language)
				$this->languages = [$language->id => $language];
		}
		if($this->languages) {
			list($language_id, $language) = each($this->languages);
			$this->language = $language;
		}

		if($this->language) {
			\Core\Session\Base::set('language_id', $this->language->id);
			setcookie('language_id', $this->language->id, time()+(86400*365),'/');
			return true;
		}
		
		throw new \Core\Exception('Missing languages in system!');
	}
	
	/**
	 * @param number $language_id
	 */
	public function issetLanguage($language_id) {
		return isset($this->languages[$language_id]);
	}
	
	/**
	 * @return \Core\Db\Table\Row\AbstractRow
	 * @throws \Core\Exception
	 */
	public function getLanguage() {
		if($this->language === null) {
			$this->setLanguage();
		} 
		return $this->language;
	}
	
	/**
	 * @return boolean
	 */
	public function isRtl() {
		$language = $this->getLanguage();
		return $language && isset($language->rtl) && $language->rtl;
	}
	
	/**
	 * @return array
	 */
    public function getLanguages() {
        if(!$this->languages) {
            $l = \Language\Language::getAll();
            foreach($l AS $l1)
                $this->languages[$l1->id] = $l1;
        }
        return $this->languages;
    }
	
	public function getDefaultLanguage() {
		return $this->getLanguage();
	}
	
	/**
	 * @return number
	 * @throws \Core\Exception
	 */
	public function getLanguageId() { 
		$language = $this->getLanguage();
		return $language->id;
	}
    
    public function registerEvent( \Core\Base\Event $e, $application ) {
    	if(self::getModule('Multilanguage')->isAccessible())
    		return;
    	$e->register('onBootstrap', [$this , 'onBootstrap']);
    }
    
    ///
    public function onBootstrap() {
    	$languages = \Language\Language::getAll();
    	$language = array_shift($languages);
    	if($language) {
    		$this->languages[$language->id] = $language;
    	} else {
    		throw new \Core\Exception('Missing languages in system!');
    	}
    }

}
