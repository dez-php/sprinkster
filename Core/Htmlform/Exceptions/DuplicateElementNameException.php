<?php

/**
 * @file    duplicateElementNameException.php
 * @brief   duplicate element name exception class
 *
 * @author Frank Hellenkamp <jonas@depage.net>
 * @author Sebastian Reinhold <sebastian@bitbernd.de>
 **/
namespace Core\Htmlform\Exceptions;

/**
 * @brief thrown when there are duplicate element names
 *
 * Element names are also used for identification, so they have to be unique.
 */
class DuplicateElementNameException extends \Core\Htmlform\Exceptions\ElementException {
}
