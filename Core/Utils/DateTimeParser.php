<?php

namespace Core\Utils;

class DateTimeParser
{
	/**
	 * @var boolean whether 'mbstring' PHP extension available. This static property introduced for
	 * the better overall performance of the class functionality. Checking 'mbstring' availability
	 * through static property with predefined status value is much faster than direct calling
	 * of function_exists('...').
	 * Intended for internal use only.
	 * @since 1.1.13
	 */
	private static $_mbstringAvailable;

	/**
	 * Converts a date string to a timestamp.
	 * @param string $value the date string to be parsed
	 * @param string $pattern the pattern that the date string is following
	 * @param array $defaults the default values for year, month, day, hour, minute and second.
	 * The default values will be used in case when the pattern doesn't specify the
	 * corresponding fields. For example, if the pattern is 'MM/dd/yyyy' and this
	 * parameter is array('minute'=>0, 'second'=>0), then the actual minute and second
	 * for the parsing result will take value 0, while the actual hour value will be
	 * the current hour obtained by date('H'). This parameter has been available since version 1.1.5.
	 * @return integer timestamp for the date string. False if parsing fails.
	 */
	public static function parse($value,$pattern='MM/dd/yyyy',$defaults=array())
	{
		if(self::$_mbstringAvailable===null)
			self::$_mbstringAvailable=extension_loaded('mbstring');

		$tokens=self::tokenize($pattern);
		$i=0;
		$n=self::$_mbstringAvailable ? mb_strlen($value,'utf-8') : strlen($value);
		foreach($tokens as $token)
		{
			switch($token)
			{
				case 'yyyy':
				{
					if(($year=self::parseInteger($value,$i,4,4))===false)
						return false;
					$i+=4;
					break;
				}
				case 'yy':
				{
					if(($year=self::parseInteger($value,$i,1,2))===false)
						return false;
					$i+=strlen($year);
					break;
				}
				case 'MMMM':
				{
					$monthName='';
					if(($month=self::parseMonth($value,$i,'wide',$monthName))===false)
						return false;
					$i+=self::$_mbstringAvailable ? mb_strlen($monthName,'utf-8') : strlen($monthName);
					break;
				}
				case 'MMM':
				{
					$monthName='';
					if(($month=self::parseMonth($value,$i,'abbreviated',$monthName))===false)
						return false;
					$i+=self::$_mbstringAvailable ? mb_strlen($monthName,'utf-8') : strlen($monthName);
					break;
				}
				case 'MM':
				{
					if(($month=self::parseInteger($value,$i,2,2))===false)
						return false;
					$i+=2;
					break;
				}
				case 'M':
				{
					if(($month=self::parseInteger($value,$i,1,2))===false)
						return false;
					$i+=strlen($month);
					break;
				}
				case 'dd':
				{
					if(($day=self::parseInteger($value,$i,2,2))===false)
						return false;
					$i+=2;
					break;
				}
				case 'd':
				{
					if(($day=self::parseInteger($value,$i,1,2))===false)
						return false;
					$i+=strlen($day);
					break;
				}
				case 'h':
				case 'H':
				{
					if(($hour=self::parseInteger($value,$i,1,2))===false)
						return false;
					$i+=strlen($hour);
					break;
				}
				case 'hh':
				case 'HH':
				{
					if(($hour=self::parseInteger($value,$i,2,2))===false)
						return false;
					$i+=2;
					break;
				}
				case 'm':
				{
					if(($minute=self::parseInteger($value,$i,1,2))===false)
						return false;
					$i+=strlen($minute);
					break;
				}
				case 'mm':
				{
					if(($minute=self::parseInteger($value,$i,2,2))===false)
						return false;
					$i+=2;
					break;
				}
				case 's':
				{
					if(($second=self::parseInteger($value,$i,1,2))===false)
						return false;
					$i+=strlen($second);
					break;
				}
				case 'ss':
				{
					if(($second=self::parseInteger($value,$i,2,2))===false)
						return false;
					$i+=2;
					break;
				}
				case 'a':
				{
				    if(($ampm=self::parseAmPm($value,$i))===false)
				        return false;
				    if(isset($hour))
				    {
				    	if($hour==12 && $ampm==='am')
				    		$hour=0;
				    	elseif($hour<12 && $ampm==='pm')
				    		$hour+=12;
				    }
					$i+=2;
					break;
				}
				default:
				{
					$tn=self::$_mbstringAvailable ? mb_strlen($token,'utf-8') : strlen($token);
					if($i>=$n || ($token{0}!='?' && (self::$_mbstringAvailable ? mb_substr($value,$i,$tn,'utf-8') : substr($value,$i,$tn))!==$token))
						return false;
					$i+=$tn;
					break;
				}
			}
		}
		if($i<$n)
			return false;

		if(!isset($year))
			$year=isset($defaults['year']) ? $defaults['year'] : date('Y');
		if(!isset($month))
			$month=isset($defaults['month']) ? $defaults['month'] : date('n');
		if(!isset($day))
			$day=isset($defaults['day']) ? $defaults['day'] : date('j');

		if(strlen($year)===2)
		{
			if($year>=70)
				$year+=1900;
			else
				$year+=2000;
		}
		$year=(int)$year;
		$month=(int)$month;
		$day=(int)$day;

		if(
			!isset($hour) && !isset($minute) && !isset($second)
			&& !isset($defaults['hour']) && !isset($defaults['minute']) && !isset($defaults['second'])
		)
			$hour=$minute=$second=0;
		else
		{
			if(!isset($hour))
				$hour=isset($defaults['hour']) ? $defaults['hour'] : date('H');
			if(!isset($minute))
				$minute=isset($defaults['minute']) ? $defaults['minute'] : date('i');
			if(!isset($second))
				$second=isset($defaults['second']) ? $defaults['second'] : date('s');
			$hour=(int)$hour;
			$minute=(int)$minute;
			$second=(int)$second;
		}

		if(\Core\Utils\Timestamp::isValidDate($year,$month,$day) && \Core\Utils\Timestamp::isValidTime($hour,$minute,$second))
			return \Core\Utils\Timestamp::getTimestamp($hour,$minute,$second,$month,$day,$year);
		else
			return false;
	}

	/*
	 * @param string $pattern the pattern that the date string is following
	 */
	private static function tokenize($pattern)
	{
		if(!($n=self::$_mbstringAvailable ? mb_strlen($pattern,'utf-8') : strlen($pattern)))
			return array();
		$tokens=array();
		$c0=self::$_mbstringAvailable ? mb_substr($pattern,0,1,'utf-8') : substr($pattern,0,1);

		for($start=0,$i=1;$i<$n;++$i)
		{
			$c=self::$_mbstringAvailable ? mb_substr($pattern,$i,1,'utf-8') : substr($pattern,$i,1);
			if($c!==$c0)
			{
				$tokens[]=self::$_mbstringAvailable ? mb_substr($pattern,$start,$i-$start,'utf-8') : substr($pattern,$start,$i-$start);
				$c0=$c;
				$start=$i;
			}
		}
		$tokens[]=self::$_mbstringAvailable ? mb_substr($pattern,$start,$n-$start,'utf-8') : substr($pattern,$start,$n-$start);
		return $tokens;
	}

	/**
	 * @param string $value the date string to be parsed
	 * @param integer $offset starting offset
	 * @param integer $minLength minimum length
	 * @param integer $maxLength maximum length
	 * @return string parsed integer value
	 */
	protected static function parseInteger($value,$offset,$minLength,$maxLength)
	{
		for($len=$maxLength;$len>=$minLength;--$len)
		{
			$v=self::$_mbstringAvailable ? mb_substr($value,$offset,$len,'utf-8') : substr($value,$offset,$len);
			if(ctype_digit($v) && (self::$_mbstringAvailable ? mb_strlen($v,'utf-8') : strlen($v))>=$minLength)
				return $v;
		}
		return false;
	}

	/**
	 * @param string $value the date string to be parsed
	 * @param integer $offset starting offset
	 * @return string parsed day period value
	 */
	protected static function parseAmPm($value, $offset)
	{
		$v=strtolower(self::$_mbstringAvailable ? mb_substr($value,$offset,2,'utf-8') : substr($value,$offset,2));
		return $v==='am' || $v==='pm' ? $v : false;
	}

	/**
	 * @param string $value the date string to be parsed.
	 * @param integer $offset starting offset.
	 * @param string $width month name width. It can be 'wide', 'abbreviated' or 'narrow'.
	 * @param string $monthName extracted month name. Passed by reference.
	 * @return string parsed month name.
	 * @since 1.1.13
	 */
	protected static function parseMonth($value,$offset,$width,&$monthName)
	{
		$valueLength=self::$_mbstringAvailable ? mb_strlen($value,'utf-8') : strlen($value);
		for($len=1; $offset+$len<=$valueLength; $len++)
		{
			$monthName=self::$_mbstringAvailable ? mb_substr($value,$offset,$len,'utf-8') : substr($value,$offset,$len);
			if(!preg_match('/^[\p{L}\p{M}]+$/u',$monthName)) // unicode aware replacement for ctype_alpha($monthName)
			{
				$monthName=self::$_mbstringAvailable ? mb_substr($monthName,0,-1,'utf-8') : substr($monthName,0,-1);
				break;
			}
		}
		$monthName=self::$_mbstringAvailable ? mb_strtolower($monthName,'utf-8') : strtolower($monthName);

		$monthNames = $monthNamesStandAlone = array();
// 		$monthNames=Yii::app()->getLocale()->getMonthNames($width,false);
// 		foreach($monthNames as $k=>$v)
// 			$monthNames[$k]=rtrim(self::$_mbstringAvailable ? mb_strtolower($v,'utf-8') : strtolower($v),'.');

// 		$monthNamesStandAlone=Yii::app()->getLocale()->getMonthNames($width,true);
// 		foreach($monthNamesStandAlone as $k=>$v)
// 			$monthNamesStandAlone[$k]=rtrim(self::$_mbstringAvailable ? mb_strtolower($v,'utf-8') : strtolower($v),'.');

		if(($v=array_search($monthName,$monthNames))===false && ($v=array_search($monthName,$monthNamesStandAlone))===false)
			return false;
		return $v;
	}
}
