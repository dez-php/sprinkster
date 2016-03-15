<?php

namespace Pin\Helper;

class Description {
	
	public static function tag($pin) {
		
		return $pin->description;
		
		$description = ' ' . html_entity_decode($pin->description, ENT_QUOTES, 'utf-8') . ' ';
		
		$search = $replace = array();
		if( preg_match_all('~\#([^ \<\>]*)~i', $description, $matches)) {
			$action = \Core\Base\Action::getInstance();
			foreach($matches[0] AS $k=>$v) {
				$search[$v] = "~(?<=.\W|\W.|^\W)" . preg_quote($v) . "(?=.\W|\W.|\W$)~";
				$replace[$v] = ' <a href="'.$action->url(array('query'=>$matches[1][$k]),'search_pin').'">'.$v.'</a> ';
			}
		}
		$description = htmlspecialchars ( $description, ENT_QUOTES, 'utf-8' );
		if($search) {
			$description = preg_replace($search, $replace, $description);
		}
		
		//extend order and get to show label's
		$extendTable = new \Base\Extend();
		$extends = $extendTable->getExtension('Pin\getAll', 'order');
		if($extends) {
			$front = \Core\Base\Front::getInstance();
			foreach($extends AS $extend) {
				$objectName = $front->formatHelperName($extend->extend);
				try {
					if(class_exists($objectName)) {
						if( method_exists($objectName, 'descriptionExtended')) {
							$description = call_user_func(array($objectName, 'descriptionExtended'), $pin);
						}
					}
				} catch (\Core\Exception $e) {}
			}
		}
		
		return trim($description);
	}
	
}

?>