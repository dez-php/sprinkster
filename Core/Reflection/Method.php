<?php

namespace Core\Reflection;

class Method extends \ReflectionMethod {
	/**
	 * Retrieve method docblock reflection
	 *
	 * @return \Core\Reflection\Docblock
	 * @throws \Core\Reflection\Exception
	 */
	public function getDocblock($reflectionClass = '\Core\Reflection\Docblock') {
		if ('' == $this->getDocComment ()) {
			require_once 'Reflection/Exception.php';
			throw new \Core\Reflection\Exception ( $this->getName () . ' does not have a docblock' );
		}
		
		$instance = new $reflectionClass ( $this );
		if (! $instance instanceof \Core\Reflection\Docblock) {
			require_once 'Reflection/Exception.php';
			throw new \Core\Reflection\Exception ( 'Invalid reflection class provided; must extend \Core\Reflection\Docblock' );
		}
		return $instance;
	}
	
	/**
	 * Get start line (position) of method
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
	 * Get reflection of declaring class
	 *
	 * @param string $reflectionClass
	 *        	Name of reflection class to use
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
	 * Get all method parameter reflection objects
	 *
	 * @param string $reflectionClass
	 *        	Name of reflection class to use
	 * @return array of \Core\Reflection\Parameter objects
	 */
	public function getParameters($reflectionClass = '\Core\Reflection\Parameter') {
		$phpReflections = parent::getParameters ();
		$JOReflections = array ();
		while ( $phpReflections && ($phpReflection = array_shift ( $phpReflections )) ) {
			$instance = new $reflectionClass ( array (
					$this->getDeclaringClass ()->getName (),
					$this->getName () 
			), $phpReflection->getName () );
			if (! $instance instanceof \Core\Reflection\Parameter) {
				require_once 'Reflection/Exception.php';
				throw new \Core\Reflection\Exception ( 'Invalid reflection class provided; must extend \Core\Reflection\Parameter' );
			}
			$JOReflections [] = $instance;
			unset ( $phpReflection );
		}
		unset ( $phpReflections );
		return $JOReflections;
	}
	
	/**
	 * Get method contents
	 *
	 * @param bool $includeDocblock        	
	 * @return string
	 */
	public function getContents($includeDocblock = true) {
		$fileContents = file ( $this->getFileName () );
		$startNum = $this->getStartLine ( $includeDocblock );
		$endNum = ($this->getEndLine () - $this->getStartLine ());
		
		return implode ( "\n", array_splice ( $fileContents, $startNum, $endNum, true ) );
	}
	
	/**
	 * Get method body
	 *
	 * @return string
	 */
	public function getBody() {
		$lines = array_slice ( file ( $this->getDeclaringClass ()->getFileName (), FILE_IGNORE_NEW_LINES ), $this->getStartLine () - 1, ($this->getEndLine () - $this->getStartLine ()) + 1, true );
		
		// Strip off lines until we come to a closing bracket
		do {
			if (count ( $lines ) == 0)
				break;
			$firstLine = array_shift ( $lines );
		} while ( strpos ( $firstLine, ')' ) === false );
		
		// If the opening brace isn't on the same line as method
		// signature, then we should pop off more lines until we find it
		if (strpos ( $firstLine, '{' ) === false) {
			do {
				if (count ( $lines ) == 0)
					break;
				$firstLine = array_shift ( $lines );
			} while ( strpos ( $firstLine, '{' ) === false );
		}
		
		// If there are more characters on the line after the opening brace,
		// push them back onto the lines stack as they are part of the body
		$restOfFirstLine = trim ( substr ( $firstLine, strpos ( $firstLine, '{' ) + 1 ) );
		if (! empty ( $restOfFirstLine )) {
			array_unshift ( $lines, $restOfFirstLine );
		}
		
		$lastLine = array_pop ( $lines );
		
		// If there are more characters on the line before the closing brace,
		// push them back onto the lines stack as they are part of the body
		$restOfLastLine = trim ( substr ( $lastLine, 0, strrpos ( $lastLine, '}' ) - 1 ) );
		if (! empty ( $restOfLastLine )) {
			array_push ( $lines, $restOfLastLine );
		}
		
		// just in case we had code on the bracket lines
		return implode ( "\n", $lines );
	}
}
