<?php

namespace Pin\Event;

use \Core\Color\Colormap;

class SetBgColor {
	
	public function PinColors($pin_id) { 
		set_time_limit(0);
		ignore_user_abort(true);
		
		$sizes = \Core\Base\Action::getModuleConfig('Base')->get('pinThumbs')->toArray();
		
		if($sizes && is_array($sizes)) {
			$size = end(array_keys($sizes));
			$pinTable = new \Pin\Pin();
			$pin = $pinTable->fetchRow(array('id = ?' => $pin_id));
			
			if($pin) {
				$image = \Pin\Helper\Image::getImage($size, $pin);
				if($image->image) {
					$map = new Colormap($image->image, Colormap::PRECISION, Colormap::RESULTS, Colormap::TRUEPER);
					$pin_bg_name = null;//$map->getBackgroundColor(false, 1);
					if(!$pin_bg_name) {
						$map->setMinPercentage(Colormap::PERCENTAGE);
						$colors = $map->getProminentColors();
						if($colors) {
							$pin_bg = 0;
							foreach ( $colors as $hex => $percents ) {
								if($pin_bg < $percents) {
									$pin_bg = $percents;
									$pin_bg_name = $hex;
								}
							}
						}
					}
					if($pin_bg_name) {
						$pinTable->update(array(
							'background_color' => $pin_bg_name
						),array('id = ?' => $pin_id));
					}
				}
			}
		}
	}
}