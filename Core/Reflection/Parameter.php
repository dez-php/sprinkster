<?php

namespace Core\Reflection;

class Parameter extends \ReflectionParameter {
	/**
	 *
	 * @var bool
	 */
	protected $_isFromMethod = false;
	
	/**
	 * Get declaring class reflection object
	 *
	 * @param string $reflectionClass
	 *        	Reflection class to use
	 * @return \Core\Reflection\Classes
	 */
	public function getDeclaringClass($reflectionClass = '\Core\Reflection\Classes') {
		$phpReflection = parent::getDeclaringClass ();
		$JOReflection = new $reflectionClass ( $phpReflection->getName () );
		if (! $JOReflection instanceof \Core\Reflection\Classes) {
			require_once 'Reflection/Exception.php';
			throw new \Core\Reflection\Exception ( 'Invalid reflection class provided; must extend \Core\Reflection\Classes' );
		}
		unset ( $phpReflection );
		return $JOReflection;
	}
	
	/**
	 * Get class reflection object
	 *
	 * @param string $reflectionClass
	 *        	Reflection class to use
	 * @return \Core\Reflection\Classes
	 */
	public function getClass($reflectionClass = '\Core\Reflection\Classes') {
		$phpReflection = parent::getClass ();
		if ($phpReflection == null) {
			return null;
		}
		
		$JOReflection = new $reflectionClass ( $phpReflection->getName () );
		if (! $JOReflection instanceof \Core\Reflection\Classes) {
			require_once 'Reflection/Exception.php';
			throw new \Core\Reflection\Exception ( 'Invalid reflection class provided; must extend \Core\Reflection\Classes' );
		}
		unset ( $phpReflection );
		return $JOReflection;
	}
	
	/**
	 * Get declaring function reflection object
	 *
	 * @param string $reflectionClass
	 *        	Reflection class to use
	 * @return \Core\Reflection\Functions \Core\Reflection\Method
	 */
	public function getDeclaringFunction($reflectionClass = null) {
		$phpReflection = parent::getDeclaringFunction ();
		if ($phpReflection instanceof ReflectionMethod) {
			$baseClass = '\Core\Reflection\Method';
			if (null === $reflectionClass) {
				$reflectionClass = $baseClass;
			}
			$JOReflection = new $reflectionClass ( $this->getDeclaringClass ()->getName (), $phpReflection->getName () );
		} else {
			$baseClass = '\Core\Reflection\Functions';
			if (null === $reflectionClass) {
				$reflectionClass = $baseClass;
			}
			$JOReflection = new $reflectionClass ( $phpReflection->getName () );
		}
		if (! $JOReflection instanceof $baseClass) {
			require_once 'Reflection/Exception.php';
			throw new \Core\Reflection\Exception ( 'Invalid reflection class provided; must extend ' . $baseClass );
		}
		unset ( $phpReflection );
		return $JOReflection;
	}
	
	/**
	 * Get parameter type
	 *
	 * @return string
	 */
	public function getType() {
		if ($docblock = $this->getDeclaringFunction ()->getDocblock ()) {
			$params = $docblock->getTags ( 'param' );
			
			if (isset ( $params [$this->getPosition ()] )) {
				return $params [$this->getPosition ()]->getType ();
			}
		}
		
		return null;
	}
}
