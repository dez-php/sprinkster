<?php

namespace Core\Db\Profiler;

class Query {
	
	/**
	 * SQL query string or user comment, set by $query argument in constructor.
	 *
	 * @var string
	 */
	protected $_query = '';
	
	/**
	 * One of the \Core\Db\Profiler constants for query type, set by $queryType
	 * argument in constructor.
	 *
	 * @var integer
	 */
	protected $_queryType = 0;
	
	/**
	 * Unix timestamp with microseconds when instantiated.
	 *
	 * @var float
	 */
	protected $_startedMicrotime = null;
	
	/**
	 * Unix timestamp with microseconds when self::queryEnd() was called.
	 *
	 * @var integer
	 */
	protected $_endedMicrotime = null;
	
	/**
	 *
	 * @var array
	 */
	protected $_boundParams = array ();
	
	protected $_trace = array();
	
	/**
	 *
	 * @var array
	 */
	
	/**
	 * Class constructor.
	 * A query is about to be started, save the query text ($query) and its
	 * type (one of the \Core\Db\Profiler::* constants).
	 *
	 * @param string $query        	
	 * @param integer $queryType        	
	 * @return void
	 */
	public function __construct($query, $queryType) {
		$this->_query = $query;
		$this->_queryType = $queryType;
		// by default, and for backward-compatibility, start the click ticking
		$this->start ();
	}
	
	/**
	 * Clone handler for the query object.
	 * 
	 * @return void
	 */
	public function __clone() {
		$this->_boundParams = array ();
		$this->_endedMicrotime = null;
		$this->start ();
	}
	
	/**
	 * Starts the elapsed time click ticking.
	 * This can be called subsequent to object creation,
	 * to restart the clock. For instance, this is useful
	 * right before executing a prepared query.
	 *
	 * @return void
	 */
	public function start() {
		$this->_startedMicrotime = microtime ( true );
	}
	
	/**
	 * Ends the query and records the time so that the elapsed time can be
	 * determined later.
	 *
	 * @return void
	 */
	public function end() {
		$this->_endedMicrotime = microtime ( true );
		$this->_trace = $this->setTrace();
	}
	
	/**
	 * Returns true if and only if the query has ended.
	 *
	 * @return boolean
	 */
	public function hasEnded() {
		return $this->_endedMicrotime !== null;
	}
	
	/**
	 * Get the original SQL text of the query.
	 *
	 * @return string
	 */
	public function getQuery() {
		return $this->_query;
	}
	
	/**
	 * Get the type of this query (one of the \Core\Db\Profiler::* constants)
	 *
	 * @return integer
	 */
	public function getQueryType() {
		return $this->_queryType;
	}
	
	/**
	 *
	 * @param string $param        	
	 * @param mixed $variable        	
	 * @return void
	 */
	public function bindParam($param, $variable) {
		$this->_boundParams [$param] = $variable;
	}
	
	/**
	 *
	 * @param array $param        	
	 * @return void
	 */
	public function bindParams(array $params) {
		if (array_key_exists ( 0, $params )) {
			array_unshift ( $params, null );
			unset ( $params [0] );
		}
		foreach ( $params as $param => $value ) {
			$this->bindParam ( $param, $value );
		}
	}
	
	/**
	 *
	 * @return array
	 */
	public function getQueryParams() {
		return $this->_boundParams;
	}
	
	/**
	 * Get the elapsed time (in seconds) that the query ran.
	 * If the query has not yet ended, false is returned.
	 *
	 * @return float false
	 */
	public function getElapsedSecs() {
		if (null === $this->_endedMicrotime) {
			return false;
		}
		
		return $this->_endedMicrotime - $this->_startedMicrotime;
	}
	
	public function getTrace() {
		return $this->_trace;
	}
	
	private function setTrace() {
		$trace = array_reverse(debug_backtrace()); 
		$calls = array();
		// Check why these are necessary in addition to the if conditional...
		array_pop($trace);
		$trace_count = count($trace);
		foreach ($trace as $index => $call) {
			// Ignore the last two calls, which are related to this class
			if ($index > $trace_count - 3) {
				continue;
			}
			// Filter out wpdb calls and calls to this class
			if (isset($call['class']) && in_array($call['class'], array('wpdb', __CLASS__))) {
				continue;
			}
			$function = isset($call['class']) ? "{$call['class']}->{$call['function']}" : $call['function'];
			$file = isset($call['file']) ? $call['file'] : null;
			if ($file) {
				$file = str_replace(\Core\Base\Init::getBase(), '', $file);
			}
			$calls[] = array(
				'function' => $function,
				'file' => $file,
				'line' => isset($call['line']) ? $call['line'] : null
			);
		}
		return $calls;		
	}
	
	public function getTraceHtml($display_files = 1) {
		$trace['trace'] = $this->getTrace();
		$html = '';
		if ($display_files) {
			foreach ($trace['trace'] as $call) {
				$reference = null;
				if ($call['file'] && $call['line']) {
					$reference = $call['file'].' ('.$call['line'].')';
				} else if ($call['file']) {
					$reference = $call['file'];
				} else {
					$reference = '(Unavailable)';
				}
				if ($reference) {
					$reference = '<span class="line-reference">'.$reference.'</span>';
				}
				$html .= '<div style="border-bottom:1px solid #aaaaaa;"><span class="function-name">'.$call['function'].'</span> '.$reference.'</div>';
			}
		} else {
			$calls = array();
			foreach ($trace['trace'] as $call) {
				$reference = null;
				if ($call['file'] && $call['line']) {
					$reference = $call['file'].' ('.$call['line'].')';
				} else if ($call['file']) {
					$reference = $call['file'];
				}
				if ($reference) {
					$reference = ' title="'.$reference.'"';
				}
				$calls[] = '<span class="function-name"'.$reference.'>'.$call['function'].'</span>';
			}
			$html = implode(', ', $calls);
		}
		return $html;
	}
}

