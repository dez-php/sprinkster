<?php

namespace Home\Helper;


class Ico {

    public static function generate($path, $sizes = []) {
        $full = BASE_PATH . DS . $path;
        if(is_file($full)) {
            $path = pathinfo($path);
            $ico = new \Core\Image\Ico($full, $sizes);
            $out = 'cache/' . $path['filename'] . '.ico';
            $out = 'cache/favicon.ico';
            if(is_file(BASE_PATH . DS . $out) && filemtime(BASE_PATH . DS . $out) > filemtime($full))
                return $out;
            if($ico->save_ico( BASE_PATH . DS . $out ))
                return $out;
        }
        return $path;
    }

} 