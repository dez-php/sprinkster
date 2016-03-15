<?php

namespace Pin\Widget\Gallery;

class Api extends \Base\Widget\PermissionWidget {
    
	public $pin;
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function result() {
    	if(!$this->pin || !$this->pin->gallery)
    		return;

    	$data = [
    		'gallery' => []
    	];

        $menu = \Base\Menu::getMenu('GalleryExtend', NULL);
        $galleryExtend = [];

        foreach($menu as $row => $widget)
        {
            if(!$widget->is_widget)
                continue;

            $config = [];

            if($widget->config)
                $config = unserialize($widget->config);

            $config['instance'] = $widget;
            $config['pin'] = $this->pin;

            $widget = (string)$this->widget($widget->widget, $config);
            if($widget) {
                $galleryExtend[] = $widget;
            }
        }

        if(!$galleryExtend && !$this->pin->gallery)
            return;

    	$pinGalleryTable = new \Pin\PinGallery();
    	$gallery = (new \Pin\PinGallery())->fetchAll([ 'pin_id = ?' => $this->pin->id ],'sort_order ASC');
    	if(!$gallery->count() && !$galleryExtend)
    		return;

        if($gallery) {
            $gallery_thumb = $this->pin->getImage('small')->image;
            $gallery_image = $this->pin->getImage('big')->image;
            $data['gallery'][] = [
                'thumb' => $gallery_thumb,
                'image' => $gallery_image
            ];
            foreach ($gallery as $image) {
                $gallery_thumb = \Pin\Helper\Image::getImage('small', $image)->image;
                $gallery_image = \Pin\Helper\Image::getImage('big', $image)->image;
                $data['gallery'][] = [
                    'thumb' => $gallery_thumb,
                    'image' => $gallery_image
                ];
            }

		    $this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
        }

        $data['galleryExtend'] = $galleryExtend;

        return $data;
    }

}
