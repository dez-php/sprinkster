<?php

namespace Core\Reflection;

class Property extends \ReflectionProperty {
	/**
	 * Get declaring class reflection object
	 *
	 * @return \Core\Reflection\Classes
	 */
	public function getDeclaringClass($reflectionClass = '\Core\Reflection\Classes') {
		$phpReflection = parent::getDeclaringClass ();
		$JOReflection = new $reflectionClass ( $phpReflection->getName () );
		if (! $JOReflection instanceof \Core\Reflection\Classes) {
			require_once 'Reflection/Exception.php';
			throw new \Core\Reflection_Exception ( 'Invalid reflection class provided; must extend \Core\Reflection\Classes' );
		}
		unset ( $phpReflection );
		return $JOReflection;
	}
	
	/**
	 * Get docblock comment
	 *
	 * @param string $reflectionClass        	
	 * @return \Core\Reflection\Docblock false if no docblock defined
	 */
	public function getDocComment($reflectionClass = '\Core\Reflection\Docblock') {
		$docblock = parent::getDocComment ();
		if (! $docblock) {
			return false;
		}
		
		$r = new $reflectionClass ( $docblock );
		if (! $r instanceof \Core\Reflection\Docblock) {
			require_once 'Reflection/Exception.php';
			throw new \Core\Reflection\Exception ( 'Invalid reflection class provided; must extend \Core\Reflection\Docblock' );
		}
		return $r;
	}
}
