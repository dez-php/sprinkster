<?php

namespace Local\Helper;

abstract class AbstractUpload {

	use \Local\Traits\Allow;
	
	/**
	 * @var array
	 */
	protected $sizes = array();
	
	/**
	 * @var array
	 */
	protected $allowedMimeImages = array();
	
	/**
	 * @var null|string
	 */
	protected $error;

	/**
	 * @var null|string
	 */
	protected $_watermark;
	
	/**
	 * @var string
	 */
	protected $_watermark_position = 'bottomright';
	
	/**
	 * @var string
	 */
	protected $sizes_name;
	
	/**
	 * @var number
	 */
	protected $max_width = 0;

	/**
	 * @var number
	 */
	protected $max_height = 0;
	
	/**
	 * @var array
	 */
	protected $image_info;

	/**
	 * @var array
	 */
	protected $file_info;
	
	/**
	 * @var default
	 */
	protected $config = [
		'width' 	=> 0,
		'height' 	=> 0,
		'crop' 		=> FALSE,
		'thumb' 	=> FALSE,
	];
	
	/**
	 * @var \Core\Locale\Translate
	 */
	protected $_;
	
	/**
	 * @param string $methodName
	 * @param array $args
	 * @throws \Core\Exception
	 * @return string
	 */
	public function __call($methodName, $args) {
        if($methodName == '_') {
        	return call_user_func_array(array($this->_,'toString'), $args);
        }
        throw new \Core\Exception(sprintf('Method "%s" does not exist and was not trapped in __call()', $methodName), 500);
    }

	/**
	 * @param string $sizes
	 */
	public function __construct($module = null, $sizes = null) {
		set_time_limit(0);
		ini_set('memory_limit', '2048M');
		if($module && $sizes) {
			$this->sizes_name = $sizes;
			$this->setSizes(\Core\Base\Action::getModuleConfig($module)->get($sizes)->toArray());
			//init watermark
			$this->_watermark();

			$this->allowedMimeImages = $this->getAllowedMime();
		}

		$this->_ = new \Translate\Locale('Front\Upload\Local', \Core\Base\Action::getModule('Language')->getLanguageId());
	}
	
	private function setSizes($sizes)
	{
		if(!is_array($sizes))
			return;

		foreach($sizes AS $folder => $size)
		{
			if(!preg_match_all('~((?P<width>\d{1,})x(?P<height>\d{1,})|(?P<crop>\-c)|(?P<thumb>\-t))~i', $size, $matches, PREG_SET_ORDER))
			{
				$this->error = sprintf($this->_('Invalid image size set "%s" for "%s"!'), $size, $this->sizes_name);
				continue;
			}

			$config = $this->config;
			$config['folder'] = $folder;

			foreach(call_user_func_array('array_merge', array_reverse($matches)) AS $c => $v)
			{
				if(!isset($this->config[$c]))
					continue;

				$config[$c] = 'boolean' == gettype($config[$c]) ? !!$v : $v;
			}

			$this->max_width = max($this->max_width, $config['width']);
			$this->max_height = max($this->max_height, $config['height']);

			$this->sizes[$folder] = $config;
		}
	}
	
	/**
	 * @return Ambigous <NULL, string>
	 */
	public function getError() {
		return $this->error;
	}

	/**
	 * @param string $image
	 * @param string $image_path
	 * @return boolean
	 */
	public function upload($image, $image_path) {
		if($this->error) {
			return false;
		} 
		$request = \Core\Http\Request::getInstance();
		$user_agent = ini_get('user_agent');
		ini_set('user_agent', $request->getServer('HTTP_USER_AGENT')?$request->getServer('HTTP_USER_AGENT'):'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.9) Gecko/20071025 Firefox/2.0.0.9');
		try {
			if($this->sizes) {
				foreach($this->sizes AS $key => $config) {
					if(!$this->_upload($image, $image_path, $config)) {
						$this->error = $this->error ? $this->error : $this->_('Unable to upload image!');
						break;
					}
				}
			} else {
				$this->error = $this->_('No images sizes is defined!');
			}
		} catch ( \Core\Exception $e ) {
			$this->error = $e->getMessage();
		}
		ini_set('user_agent', $user_agent);
		if($this->error) {
			return false;
		} else {
			$this->_delete(null, array(), true);
			return $this->image_info;
		}
	}

	/**
	 * @param string $file
	 * @param string $file_path
	 * @return boolean
	 */
	public function uploadFile($file, $file_path) {
		if($this->error) {
			return false;
		}
		$request = \Core\Http\Request::getInstance();
		$user_agent = ini_get('user_agent');
		ini_set('user_agent', $request->getServer('HTTP_USER_AGENT')?$request->getServer('HTTP_USER_AGENT'):'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.9) Gecko/20071025 Firefox/2.0.0.9');
		try {
			if(!$this->_uploadFile($file, $file_path)) {
				$this->error = $this->error ? $this->error : $this->_('Unable to upload file!');
			}
		} catch ( \Core\Exception $e ) {
			$this->error = $e->getMessage();
		}
		ini_set('user_agent', $user_agent);
		if($this->error) {
			return false;
		} else {
			return $this->file_info;
		}
	}
	
	public function getWidth()
	{
		$dimensions = $this->getDimensions();
		
		if(!$dimensions)
			return FALSE;

		return (int) $dimensions->width;
	}

	public function getHeight()
	{
		$dimensions = $this->getDimensions();

		if(!$dimensions)
			return FALSE;

		return (int) $dimensions->height;
	}

	public function getDimensions()
	{
		if(!is_array($this->image_info) || !isset($this->image_info['width']) || !isset($this->image_info['height']))
			return FALSE;

		if(0 >= $this->image_info['width'] || 0 >= $this->image_info['height'])
			return FALSE;

		$width = (int) $this->image_info['width'];
		$height = (int) $this->image_info['height'];

		$func = isset($this->config['crop']) && !!$this->config['crop'] ? 'min' : 'max';

		$factor = $func($width, $height);
		$ratio = ($func($width, $height) == $width ? $this->max_width : $this->max_height) / $factor;

		$result = new \stdClass;
		$result->width = (int) round($width * $ratio);
		$result->height = (int) round($height * $ratio);

		return $result;
	}

	/**
	 * @param null|string $file
	 * @return boolean
	 */
	public function delete($file = null) {
		if($this->sizes) {
			foreach($this->sizes AS $key => $config) {
				$this->_delete($file, $config);
			}
		}
		return true;
	}

	/**
	 * @param null|string $file
	 * @return boolean
	 */
	public function deleteFile($file = null) {
		$this->_deleteFile($file);
		return true;
	}
	
	protected function _watermark() {
		$watermark = \Base\Config::getRegExp('_watermark$');
		foreach($watermark AS $key_watermark => $image) {
			$key = preg_replace('~_watermark$~i', '', $key_watermark);
			if($image && $key && strpos($this->sizes_name, $key) === 0) {
				$this->_watermark = $image;
				$_watermark_position = \Base\Config::get($key_watermark . '_position');
				if($_watermark_position) {
					$this->_watermark_position = $_watermark_position;
				}
				break;
			}
		}
	}
	
	/**
	 * @param string $image
	 * @param string $image_path
	 * @param array $config
	 */
	abstract protected function _upload($image, $image_path, $config);
	
	/**
	 * @param string $file
	 * @param array $config
	 */
	abstract protected function _delete($file, $config, $deleteOriginalOnly = false);

	/**
	 * @param string $file
	 * @param string $file_path
	 */
	abstract protected function _uploadFile($file, $file_path);

	/**
	 * @param string $file
	 * @param array $config
	 */
	abstract protected function _deleteFile($file);
	
}