<?php

namespace Core\Reflection;

class Extension extends \ReflectionExtension {
	/**
	 * Get extension function reflection objects
	 *
	 * @param string $reflectionClass
	 *        	Name of reflection class to use
	 * @return array Array of \Core\Reflection\Functions objects
	 */
	public function getFunctions($reflectionClass = '\Core\Reflection\Functions') {
		$phpReflections = parent::getFunctions ();
		$JOReflections = array ();
		while ( $phpReflections && ($phpReflection = array_shift ( $phpReflections )) ) {
			$instance = new $reflectionClass ( $phpReflection->getName () );
			if (! $instance instanceof \Core\Reflection\Functions) {
				require_once 'Reflection/Exception.php';
				throw new \Core\Reflection\Exception ( 'Invalid reflection class provided; must extend \Core\Reflection\Functions' );
			}
			$JOReflections [] = $instance;
			unset ( $phpReflection );
		}
		unset ( $phpReflections );
		return $JOReflections;
	}
	
	/**
	 * Get extension class reflection objects
	 *
	 * @param string $reflectionClass
	 *        	Name of reflection class to use
	 * @return array Array of \Core\Reflection\Classes objects
	 */
	public function getClasses($reflectionClass = '\Core\Reflection\Classes') {
		$phpReflections = parent::getClasses ();
		$JOReflections = array ();
		while ( $phpReflections && ($phpReflection = array_shift ( $phpReflections )) ) {
			$instance = new $reflectionClass ( $phpReflection->getName () );
			if (! $instance instanceof \Core\Reflection\Classes) {
				require_once 'Reflection/Exception.php';
				throw new \Core\Reflection\Exception ( 'Invalid reflection class provided; must extend \Core\Reflection\Classes' );
			}
			$JOReflections [] = $instance;
			unset ( $phpReflection );
		}
		unset ( $phpReflections );
		return $JOReflections;
	}
}
