<?php

namespace Helper;

class HTML {

	public static function paragraphy(array $paragraphs, $css = '')
	{
		array_walk($paragraphs, function(&$paragraph) use ($css) {
			$paragraph = '<p' . ($css ? ' class="' . $css . '"' : '') . '>' . $paragraph . '</p>';
		});

		return implode('', $paragraphs);
	}

}