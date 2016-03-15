<?php

namespace Core\Text;

class String {
	
	public static function cut($text, $length = 255, $ending = '&hellip;')
	{
		if(mb_strlen($text, 'utf-8') <= $length)
			return $text;

		return mb_substr($text, 0, $length - 1, 'utf-8') . $ending;
	}

	public static function clean($text, $newlines = TRUE, $tags = '')
	{
		$result = trim(strip_tags(str_replace('&nbsp;', '', $text), $tags));

		return $newlines ? nl2br($result) : $result;
	}

	public static function plainify($text)
	{
		return self::remove_extra_spaces(self::convert_word_separators(self::convert_quotes(strip_tags(self::br2nl(html_entity_decode($text))))));
	}

	public static function convert_quotes($text, $which = [ '"', '`' ], $to = '\'')
	{
		return str_replace($which, $to, $text);
	}

	public static function convert_word_separators($text, $which = [ "\t", "\r", "\n" ], $to = ' ')
	{
		return str_replace($which, $to, $text);
	}

	public static function remove_extra_spaces($text)
	{
		return preg_replace('#\s{2,}#', ' ', $text);
	}

	public static function br2nl($text)
	{
		return preg_replace("#<br[^/>]*/?>#i", "\n", $text);
	}

	public static function alphanum($length)
	{
		$source = 'abcdefghijklmnopqrstuvwxyz0123456789';
		$result = '';

		for($i = 1; $i <= $length; $i++)
			$result .= $source[mt_rand(0, strlen($source) - 1)];

		return $result;
	}

}

?>