<?php

namespace Update;

class Update extends \Base\Model\Reference {
	
	protected $tableExist;
	
	public function __construct($config = array()) {
		parent::__construct($config);
		$this->tableExist = $this->getAdapter()->fetchOne("SHOW TABLES LIKE '".$this->_name."'");
	}
	
	public function isInstalled($name, $version) {
		if($this->tableExist) {
			if($this->hasCol('version')) {
				return $this->countByFile_Version($name, $version);
			} else {
				return $this->countByFile($name);
			}
		} else {
			return false;
		}
	}
	
	public function install($data) {
		if($this->tableExist) {
			if($this->hasCol('version')) {
				$add = $this->fetchNew();
				$add->file = $data['id'];
				$add->version = $data['version'];
				$add->save();
			} else {
				throw new \Core\Exception('you must first install "Sql Update extend" from "System core"!');
			}
		} else {
			throw new \Core\Exception('you must first install "Sql Update extend" from "System core"!');
		}
	}
	
	public function autoupdate() {
		$data_dir = glob(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'autoupdate' . DIRECTORY_SEPARATOR . '*.php');
		if($data_dir) {
			$maxTime = 0;
			$maxCheck = is_file(BASE_PATH . '/cache/autoupdate.log') ? filemtime(BASE_PATH . '/cache/autoupdate.log') : 0;
			foreach($data_dir AS $file) {
				$maxTime = max($maxTime,filemtime($file));
			}

			if($maxCheck > 0 ? $maxTime > $maxCheck : true) {
				@unlink(BASE_PATH . '/cache/autoupdate.log');
				try {
					foreach($data_dir AS $file) {
						include_once $file;
					}
                    \Core\Utils\FileHelper::removeDirectory(BASE_PATH . '/cache/MetadataCache');
                    @mkdir(BASE_PATH . '/cache/MetadataCache', 0777, true);
					file_put_contents(BASE_PATH . '/cache/autoupdate.log', time());
				} catch (\Core\Exception $e) {
					\Core\Log::log($e->getMessage());
				}
			}
		}
	}
	
}

?>