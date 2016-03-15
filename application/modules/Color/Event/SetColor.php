<?php

namespace Color\Event;

use \Core\Color\Colormap;

class SetColor {
	
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
					$map = new \Core\Color\Colormap($image->image, Colormap::PRECISION, Colormap::RESULTS, Colormap::TRUEPER);
					$map->palete = \Core\Color\Color::toPalete();
					$map->setMinPercentage(Colormap::PERCENTAGE);
					$colors = $map->getProminentColors();
					
					if($colors) {
						$colorTable = new \Color\Color();
						$images = array();
						foreach ( $colors as $hex => $percents ) {
							$color_name = $map->HexToRGB($hex);
							$color = $colorTable->fetchRow(array(
										'r = ?' => $color_name[0],
										'g = ?' => $color_name[1],
										'b = ?' => $color_name[2]		
									));
							if($color && $color->id) {
								if(isset($images[$color->id]['percents'])) {
									$images[$color->id]['percents'] += $percents;
								} else {
									$images[$color->id]['percents'] = $percents;
								}
								$images[$color->id]['color'] = $hex;
							} else {
								$color = $colorTable->fetchNew();
								$color->r = $color_name[0];
								$color->g = $color_name[1];
								$color->b = $color_name[2];
								try {
									if($color->save()) {
										$images[$color->id]['percents'] = $percents;
										$images[$color->id]['color'] = $hex;
										$langsTable = new \Language\Language();
										$langs = $langsTable->fetchAll();
										$colorDescriptionTable = new \Color\ColorDescription();
										foreach($langs AS $l) {
											$new = $colorDescriptionTable->fetchNew();
											$new->color_id = $color->id;
											$new->title = implode(',',$color_name);
											$new->language_id = $l->id;
											$new->save();
										}
									}
								} catch (\Core\Exception $e) { }
							}
						}
						$p2cTable = new \Pin\PinToColor();
						$p2cTable->delete(array('pin_id = ?' => $pin->id));
						$pin_bg_name = null;
						$pin_bg = 0;
						foreach($images AS $color_id => $image) {
							$new = $p2cTable->fetchNew();
							$new->pin_id = $pin->id;
							$new->color_id = $color_id;
							$new->percent = $image['percents'];
							$new->save();
							if($pin_bg < $image['percents']) {
								$pin_bg = $image['percents'];
								$pin_bg_name = $image['color'];
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
}