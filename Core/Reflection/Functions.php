<?php

namespace Core\Reflection;

class Functions extends \ReflectionFunction {
	/**
	 * Get function docblock
	 *
	 * @param string $reflectionClass
	 *        	Name of reflection class to use
	 * @return \Core\Reflection\Docblock
	 */
	public function getDocblock($reflectionClass = '\Core\Reflection\Docblock') {
		if ('' == ($comment = $this->getDocComment ())) {
			require_once 'Reflection/Exception.php';
			throw new \Core\Reflection\Exception ( $this->getName () . ' does not have a docblock' );
		}
		$instance = new $reflectionClass ( $comment );
		if (! $instance instanceof \Core\Reflection\Docblock) {
			require_once 'Reflection/Exception.php';
			throw new \Core\Reflection\Exception ( 'Invalid reflection class provided; must extend \Core\Reflection\Docblock' );
		}
		return $instance;
	}
	
	/**
	 * Get start line (position) of function
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
	 * Get contents of function
	 *
	 * @param bool $includeDocblock        	
	 * @return string
	 */
	public function getContents($includeDocblock = true) {
		return implode ( "\n", array_splice ( file ( $this->getFileName () ), $this->getStartLine ( $includeDocblock ), ($this->getEndLine () - $this->getStartLine ()), true ) );
	}
	
	/**
	 * Get function parameters
	 *
	 * @param string $reflectionClass
	 *        	Name of reflection class to use
	 * @return array Array of \Core\Reflection\Parameter
	 */
	public function getParameters($reflectionClass = '\Core\Reflection\Parameter') {
		$phpReflections = parent::getParameters ();
		$JOReflections = array ();
		while ( $phpReflections && ($phpReflection = array_shift ( $phpReflections )) ) {
			$instance = new $reflectionClass ( $this->getName (), $phpReflection->getName () );
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
	 * Get return type tag
	 *
	 * @return \Core\Reflection\Docblock_Tag_Return
	 */
	public function getReturn() {
		$docblock = $this->getDocblock ();
		if (! $docblock->hasTag ( 'return' )) {
			require_once 'Reflection/Exception.php';
			throw new \Core\Reflection\Exception ( 'Function does not specify an @return annotation tag; cannot determine return type' );
		}
		$tag = $docblock->getTag ( 'return' );
		$return = \Core\Reflection\Docblock_Tag::factory ( '@return ' . $tag->getDescription () );
		return $return;
	}
}
