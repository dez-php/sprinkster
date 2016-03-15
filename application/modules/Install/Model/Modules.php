<?php

namespace Install;

class Modules extends \Base\Model\Reference {

    protected $_name = 'module';

    protected static $_modules = [];
    protected static $_modulesConfig = [];

    public static function isInstalled($module) {
        $module = strtolower($module);
        static $modules = null;
        if($modules===null) {
            $self = new self();
            $modulesData = $self->fetchAll();
            foreach($modulesData AS $m) {
                $modules[strtolower($m->module)] = $m->install;
            }
        }
        return isset($modules[$module]) ? ($modules[$module] ? \Core\Base\Action::allow($module) : false) : false;
    }

    public static function initModules() {
        if(!self::$_modules) {
            $front = \Core\Base\Action::getInstance()->getFrontController ();
            $modules = glob ( $front->getModuleDirectory () . DIRECTORY_SEPARATOR . '*' );

            if ($modules) {
                foreach ( $modules as $module ) {
                    $name = strtolower ( basename ( $module ) );
                    $namespace = $front->formatModuleName ( $name );
                    if (file_exists ( $module . DIRECTORY_SEPARATOR . 'Module.php' )) {
                        if (! isset ( self::$_modules [$namespace] )) {
                            if (! class_exists ( $namespace . '\\Module', false )) {
                                include_once $module . DIRECTORY_SEPARATOR . 'Module.php';
                            }
                            $moduleName = $namespace . '\\Module';
                            $m = new $moduleName ();
                            if( $m instanceof \Core\Base\Module ) {
                                self::$_modules [$namespace] = $m;
                                $options = $m->getConfig ();
                                self::setModulesConfig($namespace, $options);
                            }
                        }
                    }
                }
            }
        }

        return self::$_modules;
    }

    public static function getModules($name = null) {
        self::initModules();
        if ($name !== null) {
            $front = \Core\Base\Action::getInstance()->getFrontController ();
            $namespace = $front->formatModuleName ( $name );
            return isset ( self::$_modules [$namespace] ) ? self::$_modules [$namespace] : null;
        } else {
            return self::$_modules;
        }
    }

    public static function getModulesConfig($name = null) {
        self::initModules();
        if ($name !== null) {
            $front = \Core\Base\Action::getInstance()->getFrontController ();
            $namespace = $front->formatModuleName ( $name );
            return isset ( self::$_modulesConfig [$namespace] ) ? self::$_modulesConfig [$namespace] : null;
        } else {
            return self::$_modulesConfig;
        }
    }

    public static function getModulesInstall($name) {
        self::initModules();
        $name = strtolower($name);
        $tmp = [];
        foreach(self::$_modulesConfig AS $namespace => $m) {
            if(!self::isInstalled($namespace))
                continue;
            if( is_object( $data = $m->get('install') ) && $data instanceof \Core\Config\Main && !is_null($install = $data->get($name))) {
                $tmp[] = $install;
            }
        }
        return $tmp;
    }

    public static function getModulesAfterInstall($name) {
        $name = strtolower($name);
        if( is_object($data = self::getModulesConfig($name)) && $data instanceof \Core\Config\Main && !is_null($install = $data->get('install'))) {
            $tmp = [];
            foreach($install->toArray() AS $module => $className) {
                if(!self::isInstalled($module))
                    continue;
                $tmp[] = [$className, $module];
            }
            return $tmp;
        }
        return [];
    }

    public static function getModulesUninstall($name) {
        self::initModules();
        $name = strtolower($name);
        $tmp = [];
        foreach(self::$_modulesConfig AS $namespace => $m) {
            if(!self::isInstalled($namespace))
                continue;
            if( is_object( $data = $m->get('uninstall') ) && $data instanceof \Core\Config\Main && !is_null($uninstall = $data->get($name))) {
                $tmp[] = $uninstall;
            }
        }
        return $tmp;
    }

    public static function getModulesAfterUninstall($name) {
        $name = strtolower($name);
        if( is_object($data = self::getModulesConfig($name)) && $data instanceof \Core\Config\Main && !is_null($uninstall = $data->get('uninstall'))) {
            $tmp = [];
            foreach($uninstall->toArray() AS $module => $className) {
                if(!self::isInstalled($module))
                    continue;
                $tmp[] = [$className, $module];
            }
            return $tmp;
        }
        return [];
    }

    public static function getModulesDelete($name) {
        self::initModules();
        $name = strtolower($name);
        $tmp = [];
        foreach(self::$_modulesConfig AS $namespace => $m) {
            if(!self::isInstalled($namespace))
                continue;
            if( is_object( $data = $m->get('delete') ) && $data instanceof \Core\Config\Main && !is_null($delete = $data->get($name))) {
                $tmp[] = $delete;
            }
        }
        return $tmp;
    }

    public static function getModulesAfterDelete($name) {
        $name = strtolower($name);
        if( is_object($data = self::getModulesConfig($name)) && $data instanceof \Core\Config\Main && !is_null($delete = $data->get('delete'))) {
            $tmp = [];
            foreach($delete->toArray() AS $module => $className) {
                if(!self::isInstalled($module))
                    continue;
                $tmp[] = [$className, $module];
            }
            return $tmp;
        }
        return [];
    }

    private static function setModulesConfig($namespace = null, $options = []) {
        if ($options) {
            if (is_string ( $options )) {
                $options = self::_loadConfig ( $options );
            } elseif ($options instanceof \Core\Config\Main) {
                $options = $options->toArray ();
            } elseif (! is_array ( $options )) {
                throw new \Core\Exception ( 'Invalid options provided; must be location of config file, a config object, or an array' );
            }
        }
        $front = \Core\Base\Action::getInstance()->getFrontController ();
        $namespace = $front->formatModuleName ( $namespace );
        self::$_modulesConfig [$namespace] = new \Core\Config\Main ( is_array($options) ? $options : array() );
    }

    protected static function _loadConfig($file) {
        if (! file_exists ( $file )) {
            throw new \Core\Exception("Missing file '" . $file . "'!");
        }
        $environment = \Core\Base\Action::getInstance()->getApplication()->getEnvironment ();
        $suffix = strtolower ( pathinfo ( $file, PATHINFO_EXTENSION ) );

        switch ($suffix) {
            case 'ini' :
                require_once 'Config/Ini.php';
                $config = new \Core\Config\Ini ( $file, $environment );
                break;

            case 'php' :
                require_once 'Config/Php.php';
                $config = new \Core\Config\Php ( $file, $environment );
                break;

            default :
                throw new \Core\Exception ( 'Invalid configuration file provided; unknown config type' );
        }

        return $config->toArray ();
    }

}

?>