<?php

/**
 * @file    unknownElementTypeException.php
 * @brief   unknown element type exception class
 *
 * @author Frank Hellenkamp <jonas@depage.net>
 * @author Sebastian Reinhold <sebastian@bitbernd.de>
 **/
namespace Core\Htmlform\Exceptions;

/**
 * @brief thrown when attemting to instantiate an inexistent element class
 */
class UnknownElementTypeException extends \Core\Htmlform\Exceptions\ElementException {
	public function __construct() {
		parent::__construct ( "Unknown element type." );
	}
}
