<?php

/**
 * @file    step.php
 * @brief   step container element
 *
 * @author Frank Hellenkamp <jonas@depage.net>
 * @author Sebastian Reinhold <sebastian@bitbernd.de>
 **/
namespace Core\Htmlform\Elements;

/**
 * @brief Steps break up forms into separate consecutive parts
 *
 * @link steps.php Example form @endlink
 *      
 */
class Step extends \Core\Htmlform\Elements\Fieldset {
	// {{{ __toString()
	/**
	 * @brief renders step container to HTML
	 *
	 * If the step contains elements it calls their rendering methods.
	 * (unlike fieldsets, steps themselves aren't rendered)
	 *
	 * @return $renderedElement (string) HTML rendered element
	 *        
	 */
	public function __toString() {
		$renderedElements = '';
		foreach ( $this->elementsAndHtml as $element ) {
			$renderedElements .= $element;
		}
		return $renderedElements;
	}
	// }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
