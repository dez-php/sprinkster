<?php

namespace Pin\Helper;

class Gallery {

	private static $image_info = array();
	private static $baseUrl = null;
	
	/**
	 * @param string $size
	 * @throws \Core\Exception
	 * @return Ambigous <multitype:number boolean >
	 */
	private static function getSizesData($size) {
		static $sizes = null, $size_data = array();
		if($sizes === null)
			$sizes = \Core\Base\Action::getModuleConfig('Base')->get('pinGallery')->toArray();

		if(!isset($size_data[$size])) {
			if(!array_key_exists($size, $sizes)) {
				throw new \Core\Exception('Image size "'.$size.'" is not defined!');
			}
			$size_data[$size] = array(
					'width' => 0,
					'height' => 0,
					'crop' => false,
					'thumb' => false
			);
			if(preg_match_all('~((?P<width>\d{1,})x(?P<height>\d{1,})|(?P<crop>\-c)|(?P<thumb>\-t))~i', $sizes[$size], $matches, PREG_SET_ORDER)) {
				foreach(call_user_func_array('array_merge', array_reverse($matches)) AS $c => $v) {
					if(isset($size_data[$size][$c])) {
						if(gettype($size_data[$size][$c]) == 'boolean') {
							$size_data[$size][$c] = (bool)$v;
						} else {
							$size_data[$size][$c] = $v;
						}
					}
				}
			} else {
				throw new \Core\Exception('Invalid image size set "' . $size . '" for "pinthumbs"!');
			}
		}
		return $size_data[$size];
	}
	
	/**
	 * @param string $size
	 * @param \Core\Db\Table\Row\AbstractRow $pin
	 * @throws \Core\Exception
	 * @return \stdClass
	 */
	public static function getImage($size, $pin) {
		if(!$pin->width || !$pin->height) {
			throw new \Core\Exception('Sorce image dimensions is not set!');
		}
		$sizes = self::getSizesData($size); 
		$path = dirname($pin->image);
		$filename = basename($pin->image);
		$object = new \stdClass();
		$host = $pin->store_host;
		if($pin->store_host == '{local}') {
			$host = trim(\Core\Http\Request::getInstance()->getBaseUrl(),'/');
		}
		$object->image = $host . $path . '/' . $size . '/' . $filename;
		if($sizes['width'] && $sizes['height']) {
			$object->width = (int)$sizes['width'];
			$object->height = (int)$sizes['height'];
		} else if(!$sizes['width'] && $sizes['height']) {
			$object->height = (int)$sizes['height'];
			$object->width = (int)ceil($pin->width / ($pin->height/$sizes['height']));
		} else if($sizes['width'] && !$sizes['height']) {
			$object->width = (int)$sizes['width'];
			$object->height = (int)ceil($pin->height / ($pin->width/$sizes['width']));
		} else {
			throw new \Core\Exception('Image size dimensions is not set!');
		}
		return $object;
	}
	
	/**
	 * @param string $size
	 * @throws \Core\Exception
	 * @return \stdClass
	 */
	public static function noImage($size) {
		$config = self::getSizesData($size);
		$no_image = \Base\Config::get('no_image');
		if($no_image && file_exists(BASE_PATH . '/uploads/data/' . $no_image)) {
			$ext = \Core\File\Ext::getExtFromMime(\Core\File\Ext::getMimeFromFile($no_image));
			$no_image_full = BASE_PATH . '/uploads/data/' . $no_image;
		} else {
			$ext = 'png';
			$no_image_full = BASE_PATH . '/assets/images/no_image.png';
		}
		
		if (!file_exists(BASE_PATH . '/uploads/noimage/pinthumbs/' . $size . '.' . $ext) || (filemtime($no_image_full) > filemtime(BASE_PATH . '/uploads/noimage/pinthumbs/' . $size . '.' . $ext))) {
			if(!file_exists(BASE_PATH . '/uploads/noimage/pinthumbs/') || !is_dir(BASE_PATH . '/uploads/noimage/pinthumbs/')) {
				@mkdir(BASE_PATH . '/uploads/noimage/pinthumbs/', 0777, true);
				@chmod(BASE_PATH . '/uploads/noimage/pinthumbs/', 0777);
			}
			
			//resize if not exist
			$thumb = new \Local\Library\Thumb($no_image_full);
			if($config['width'] && $config['height']) {
				if($config['thumb']) {
					$thumb->thumb($config['width'], $config['height']);
				} else if($config['crop']) {
					$thumb->resize_crop($config['width'], $config['height']);
				} else {
					$thumb->resize($config['width'], $config['height']);
				}
			} else if($config['width'] && !$config['height']) {
				$thumb->resizeWidth($config['width']);
			} else if(!$config['width'] && $config['height']) {
				$thumb->resizeHeight($config['height']);
			} else {
				throw new \Core\Exception('Image dimensions is not defined!');
			}
			if($thumb->save(BASE_PATH . '/uploads/noimage/pinthumbs/' . $size . '.' . $ext)) {
				$info = $thumb->getInfo();
			} else {
				throw new \Core\Exception('Unable to save image: "/uploads/noimage/pinthumbs/' . $size . '.' . $ext . '"!');
			}
		} else {
			if(!isset(self::$image_info[$size])) {
				if( is_array($img_info = @getimagesize(BASE_PATH . '/uploads/noimage/pinthumbs/' . $size . '.' . $ext)) ) {
					$info = self::$image_info[$size] = array(
						'width'  => $img_info[0],
						'height' => $img_info[1],
						'bits'   => $img_info['bits'],
						'mime'   => $img_info['mime'],
						'extension' => \Core\File\Ext::getExtFromMime($img_info['mime'])
					);
				} else {
					throw new \Core\Exception('File "/uploads/noimage/pinthumbs/' . $size . '.' . $ext . '" is not walid image!');
				}
			} else {
				$info = self::$image_info[$size];
			}
		}
		
		if(self::$baseUrl === null) {
			self::$baseUrl = \Core\Http\Request::getInstance()->getBaseUrl();
		}
		
		$object = new \stdClass();
		foreach($info AS $k => $v) {
			$object->{$k} = $v;
		}
		$object->image = self::$baseUrl . 'uploads/noimage/pinthumbs/' . $size . '.' . $ext;
		
		return $object;
	}
	
}