<?php

namespace Core\Debug;

class HelperLoger {

	const LEVEL_TRACE='trace';
	const LEVEL_WARNING='warning';
	const LEVEL_ERROR='error';
	const LEVEL_INFO='info';
	const LEVEL_PROFILE='profile';
	public $autoFlush=10000;
	public $autoDump=false;
	private $_logs=array();
	private $_logCount=0;
	private $_levels;
	private $_categories;
	private $_except=array();
	private $_timings;
	private $_processing=false;
	public function log($message,$level='info',$category='application')
	{
		$this->_logs[]=array($message,$level,$category,microtime(true));
		$this->_logCount++;
		if($this->autoFlush>0 && $this->_logCount>=$this->autoFlush && !$this->_processing)
		{
			$this->_processing=true;
			$this->flush($this->autoDump);
			$this->_processing=false;
		}
	}
	public function getLogs($levels='',$categories=array(), $except=array())
	{
		$this->_levels=preg_split('/[\s,]+/',strtolower($levels),-1,PREG_SPLIT_NO_EMPTY);
		if (is_string($categories))
			$this->_categories=preg_split('/[\s,]+/',strtolower($categories),-1,PREG_SPLIT_NO_EMPTY);
		else
			$this->_categories=array_filter(array_map('strtolower',$categories));
		if (is_string($except))
			$this->_except=preg_split('/[\s,]+/',strtolower($except),-1,PREG_SPLIT_NO_EMPTY);
		else
			$this->_except=array_filter(array_map('strtolower',$except));
		$ret=$this->_logs;
		if(!empty($levels))
			$ret=array_values(array_filter($ret,array($this,'filterByLevel')));
		if(!empty($this->_categories) || !empty($this->_except))
			$ret=array_values(array_filter($ret,array($this,'filterByCategory')));
		return $ret;
	}
	private function filterByCategory($value)
	{
		return $this->filterAllCategories($value, 2);
	}
	private function filterTimingByCategory($value)
	{
		return $this->filterAllCategories($value, 1);
	}
	private function filterAllCategories($value, $index)
	{
		$cat=strtolower($value[$index]);
		$ret=empty($this->_categories);
		foreach($this->_categories as $category)
		{
			if($cat===$category || (($c=rtrim($category,'.*'))!==$category && strpos($cat,$c)===0))
				$ret=true;
		}
		if($ret)
		{
			foreach($this->_except as $category)
			{
				if($cat===$category || (($c=rtrim($category,'.*'))!==$category && strpos($cat,$c)===0))
					$ret=false;
			}
		}
		return $ret;
	}
	private function filterByLevel($value)
	{
		return in_array(strtolower($value[1]),$this->_levels);
	}
	public function getExecutionTime()
	{
		return microtime(true)-Debug::getBeginTime();
	}
	public function getMemoryUsage()
	{
		if(function_exists('memory_get_usage'))
			return memory_get_usage();
		else
		{
			$output=array();
			if(strncmp(PHP_OS,'WIN',3)===0)
			{
				exec('tasklist /FI "PID eq ' . getmypid() . '" /FO LIST',$output);
				return isset($output[5])?preg_replace('/[\D]/','',$output[5])*1024 : 0;
			}
			else
			{
				$pid=getmypid();
				exec("ps -eo%mem,rss,pid | grep $pid", $output);
				$output=explode("  ",$output[0]);
				return isset($output[1]) ? $output[1]*1024 : 0;
			}
		}
	}
	public function getProfilingResults($token=null,$categories=null,$refresh=false)
	{
		if($this->_timings===null || $refresh)
			$this->calculateTimings();
		if($token===null && $categories===null)
			return $this->_timings;
		$timings = $this->_timings;
		if($categories!==null) {
			$this->_categories=preg_split('/[\s,]+/',strtolower($categories),-1,PREG_SPLIT_NO_EMPTY);
			$timings=array_filter($timings,array($this,'filterTimingByCategory'));
		}
		$results=array();
		foreach($timings as $timing)
		{
			if($token===null || $timing[0]===$token)
				$results[]=$timing[2];
		}
		return $results;
	}
	private function calculateTimings()
	{
		$this->_timings=array();
		$stack=array();
		foreach($this->_logs as $log)
		{
			if($log[1]!==self::LEVEL_PROFILE)
				continue;
			list($message,$level,$category,$timestamp)=$log;
			if(!strncasecmp($message,'begin:',6))
			{
				$log[0]=substr($message,6);
				$stack[]=$log;
			}
			elseif(!strncasecmp($message,'end:',4))
			{
				$token=substr($message,4);
				if(($last=array_pop($stack))!==null && $last[0]===$token)
				{
					$delta=$log[3]-$last[3];
					$this->_timings[]=array($message,$category,$delta);
				}
				else
					throw new \Exception('Profile found a mismatching code block "'.$token.'". Make sure the calls to HelperFront::beginProfile() and HelperFront::endProfile() be properly nested.');
			}
		}
		$now=microtime(true);
		while(($last=array_pop($stack))!==null)
		{
			$delta=$now-$last[3];
			$this->_timings[]=array($last[0],$last[2],$delta);
		}
	}
	
}