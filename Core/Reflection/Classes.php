<?php

namespace Core\Reflection;

class Classes extends \ReflectionClass {
	/**
	 * Return the reflection file of the declaring file.
	 *
	 * @return \Core\Reflection\File
	 */
	public function getDeclaringFile($reflectionClass = '\Core\Reflection\File') {
		$instance = new $reflectionClass ( $this->getFileName () );
		if (! $instance instanceof \Core\Reflection\File) {
			require_once 'Reflection/Exception.php';
			throw new \Core\Reflection\Exception ( 'Invalid reflection class specified; must extend \Core\Reflection\File' );
		}
		return $instance;
	}
	
	/**
	 * Return the classes Docblock reflection object
	 *
	 * @param string $reflectionClass
	 *        	Name of reflection class to use
	 * @return \Core\Reflection\Docblock
	 * @throws \Core\Reflection\Exception for missing docblock or invalid
	 *         reflection class
	 */
	public function getDocblock($reflectionClass = '\Core\Reflection\Docblock') {
		if ('' == $this->getDocComment ()) {
			require_once 'Reflection/Exception.php';
			throw new \Core\Reflection\Exception ( $this->getName () . ' does not have a docblock' );
		}
		
		$instance = new $reflectionClass ( $this );
		if (! $instance instanceof \Core\Reflection\Docblock) {
			require_once 'Reflection/Exception.php';
			throw new \Core\Reflection\Exception ( 'Invalid reflection class specified; must extend \Core\Reflection\Docblock' );
		}
		return $instance;
	}
	
	/**
	 * Return the start line of the class
	 *
	 * @param bool $includeDocComment        	
	 * @return int
	 */
	public function getStartLine($includeDocComment = false) {
		if ($includeDocComment) {
			if ($this->getDocComment () != '') {
				return $this->getDocblock ()->getStartLine ();
			}
		}
		
		return parent::getStartLine ();
	}
	
	/**
	 * Return the contents of the class
	 *
	 * @param bool $includeDocblock        	
	 * @return string
	 */
	public function getContents($includeDocblock = true) {
		$filename = $this->getFileName ();
		$filelines = file ( $filename );
		$startnum = $this->getStartLine ( $includeDocblock );
		$endnum = $this->getEndLine () - $this->getStartLine ();
		
		return implode ( '', array_splice ( $filelines, $startnum, $endnum, true ) );
	}
	
	/**
	 * Get all reflection objects of implemented interfaces
	 *
	 * @param string $reflectionClass
	 *        	Name of reflection class to use
	 * @return array Array of \Core\Reflection\Classes
	 */
	public function getInterfaces($reflectionClass = '\Core\Reflection\Classes') {
		$phpReflections = parent::getInterfaces ();
		$JOReflections = array ();
		while ( $phpReflections && ($phpReflection = array_shift ( $phpReflections )) ) {
			$instance = new $reflectionClass ( $phpReflection->getName () );
			if (! $instance instanceof \Core\Reflection\Classes) {
				require_once 'Reflection/Exception.php';
				throw new \Core\Reflection\Exception ( 'Invalid reflection class specified; must extend \Core\Reflection\Classes' );
			}
			$JOReflections [] = $instance;
			unset ( $phpReflection );
		}
		unset ( $phpReflections );
		return $JOReflections;
	}
	
	/**
	 * Return method reflection by name
	 *
	 * @param string $name        	
	 * @param string $reflectionClass
	 *        	Reflection class to utilize
	 * @return \Core\Reflection\Method
	 */
	public function getMethod($name, $reflectionClass = '\Core\Reflection\Method') {
		$phpReflection = parent::getMethod ( $name );
		$JOReflection = new $reflectionClass ( $this->getName (), $phpReflection->getName () );
		
		if (! $JOReflection instanceof \Core\Reflection\Method) {
			require_once 'Reflection/Exception.php';
			throw new \Core\Reflection\Exception ( 'Invalid reflection class specified; must extend \Core\Reflection\Method' );
		}
		
		unset ( $phpReflection );
		return $JOReflection;
	}
	
	/**
	 * Get reflection objects of all methods
	 *
	 * @param string $filter        	
	 * @param string $reflectionClass
	 *        	Reflection class to use for methods
	 * @return array Array of \Core\Reflection\Method objects
	 */
	public function getMethods($filter = -1, $reflectionClass = '\Core\Reflection\Method') {
		$phpReflections = parent::getMethods ( $filter );
		$JOReflections = array ();
		while ( $phpReflections && ($phpReflection = array_shift ( $phpReflections )) ) {
			$instance = new $reflectionClass ( $this->getName (), $phpReflection->getName () );
			if (! $instance instanceof \Core\Reflection\Method) {
				require_once 'Reflection/Exception.php';
				throw new \Core\Reflection\Exception ( 'Invalid reflection class specified; must extend \Core\Reflection\Method' );
			}
			$JOReflections [] = $instance;
			unset ( $phpReflection );
		}
		unset ( $phpReflections );
		return $JOReflections;
	}
	
	/**
	 * Get parent reflection class of reflected class
	 *
	 * @param string $reflectionClass
	 *        	Name of Reflection class to use
	 * @return \Core\Reflection\Classes
	 */
	public function getParentClass($reflectionClass = '\Core\Reflection\Classes') {
		$phpReflection = parent::getParentClass ();
		if ($phpReflection) {
			$JOReflection = new $reflectionClass ( $phpReflection->getName () );
			if (! $JOReflection instanceof \Core\Reflection\Classes) {
				require_once 'Reflection/Exception.php';
				throw new \Core\Reflection\Exception ( 'Invalid reflection class specified; must extend \Core\Reflection\Classes' );
			}
			unset ( $phpReflection );
			return $JOReflection;
		} else {
			return false;
		}
	}
	
	/**
	 * Return reflection property of this class by name
	 *
	 * @param string $name        	
	 * @param string $reflectionClass
	 *        	Name of reflection class to use
	 * @return \Core\Reflection\Property
	 */
	public function getProperty($name, $reflectionClass = '\Core\Reflection\Property') {
		$phpReflection = parent::getProperty ( $name );
		$JOReflection = new $reflectionClass ( $this->getName (), $phpReflection->getName () );
		if (! $JOReflection instanceof \Core\Reflection\Property) {
			require_once 'Reflection/Exception.php';
			throw new \Core\Reflection\Exception ( 'Invalid reflection class specified; must extend \Core\Reflection\Property' );
		}
		unset ( $phpReflection );
		return $JOReflection;
	}
	
	/**
	 * Return reflection properties of this class
	 *
	 * @param int $filter        	
	 * @param string $reflectionClass
	 *        	Name of reflection class to use
	 * @return array Array of \Core\Reflection\Property
	 */
	public function getProperties($filter = -1, $reflectionClass = '\Core\Reflection\Property') {
		$phpReflections = parent::getProperties ( $filter );
		$JOReflections = array ();
		while ( $phpReflections && ($phpReflection = array_shift ( $phpReflections )) ) {
			$instance = new $reflectionClass ( $this->getName (), $phpReflection->getName () );
			if (! $instance instanceof \Core\Reflection\Property) {
				require_once 'Reflection/Exception.php';
				throw new \Core\Reflection\Exception ( 'Invalid reflection class specified; must extend \Core\Reflection\Property' );
			}
			$JOReflections [] = $instance;
			unset ( $phpReflection );
		}
		unset ( $phpReflections );
		return $JOReflections;
	}
}
