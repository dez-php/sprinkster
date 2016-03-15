<?php

namespace Multilanguage;

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
		if( /*from parameter*/ $this->issetLanguage($language_id)) {
			$this->language = $this->languages[$language_id];
		} else if( /*user settings*/ \User\User::getUserData()->language_id && $this->issetLanguage(\User\User::getUserData()->language_id)) {
			$this->language = $this->languages[\User\User::getUserData()->language_id];
		} else if( /* from session */ \Core\Session\Base::get('language_id') && $this->issetLanguage(\Core\Session\Base::get('language_id'))) {
			$this->language = $this->languages[\Core\Session\Base::get('language_id')];
		} else if( /*cookie*/ isset($_COOKIE['language_id']) && $this->issetLanguage($_COOKIE['language_id']) ) {
			$this->language = $this->languages[$_COOKIE['language_id']];
		} else if( /*system settings*/ \Base\Config::get('language_id') && $this->issetLanguage(\Base\Config::get('language_id')) ) {
			$this->language = $this->languages[\Base\Config::get('language_id')];
		} else {
			//find by locale
			$locales = \Core\Locale::getBrowser();
			if(is_array($locales)) {
				arsort($locales);
				foreach($locales AS $locale) {
					foreach ($this->languages as $language) {
						
					}
				}
			}
			$locales = \Core\Locale::getBrowser();
	    	if(is_array($locales)) {
	    		arsort($locales);
	    		$break = false;
	    		foreach($locales AS $locale => $quality) {
	    			$locale = strtolower($locale);
	    			foreach ($this->languages as $language) {
	    				$array_langs = array_merge(explode(',',$language->locale), array($language->code));
	    				$array_langs = array_map('strtolower',$array_langs);
	    				if(in_array($locale, $array_langs)) {
	    					$this->language = $language;
	    					$break = true;
	    					break;
	    				}
	    			} 
	    			if($break) { break; }
	    		}
	    	}
	    	//set first language for default
	    	if(!$this->language) {
				foreach ($this->languages as $language) {
					$this->language = $language;
					break;
				}
	    	}
		}

		if($this->language) {
			if(\User\User::getUserData()->id && \User\User::getUserData()->language_id != $this->language->id) {
				(new \User\User())->update(['language_id' => $this->language->id], ['id = ?' => \User\User::getUserData()->id]);
			}
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
		return $this->languages[\Base\Config::get('language_id')];
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
    	$e->register('onBootstrap', [ $this, 'onBootstrap' ]);
    }

    public function onBootstrap()
	{
		$languages = $this->getLanguages();
		$language = array_shift($languages);
		if($language) {
			$this->languages[$language->id] = $language;
		} else {
			throw new \Core\Exception('Missing languages in system!');
		}
		
		$request = $this->getRequest();
		if($request->isPost() && $request->getPost('language_switch')) {
			$this->setLanguage($request->getRequest('language_switch'));
		} else {
			$this->setLanguage($request->getRequest('language_id'));
		}
		if($request->isPost() && $request->getPost('language_switch')) {
			$this->redirect($request->getFullUrl());
		}
		\Core\Base\Init::getInstance()->setModules ( 'Language', $this );
	}

    /**
     * Set module configuration
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }
    
    public function isAccessible() {
    	return \Install\Modules::isInstalled(__NAMESPACE__);
    }
}
