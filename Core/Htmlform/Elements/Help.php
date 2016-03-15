<?php

/**
 * @file    textarea.php
 * @brief   textarea input element
 *
 * @author Frank Hellenkamp <jonas@depage.net>
 * @author Sebastian Reinhold <sebastian@bitbernd.de>
 **/
namespace Core\Htmlform\Elements;

/**
 * @brief HTML textarea element.
 *
 * @section usage
 *
 * @code
 * <?php/**
 * @brief HTML textarea element.
 *
 * @section usage
 *
 * @code
 * <?php
 * $form = new depage\htmlform\htmlform('myform');
 *
 * // add a textarea field
 * $form->addTextarea('comment', array(
 * 'label' => 'Comment box',
 * ));
 *
 * // process form
 * $form->process();
 *
 * // Display the form.
 * echo ($form);
 * ?>
 * @endcode
 */
class Help extends \Core\Htmlform\Elements\Text {
	// {{{ __toString()
	/**
	 * @brief Renders element to HTML.
	 *
	 * @return (string) HTML rendered element
	 *        
	 */
	public function __toString() {
		$value = html_entity_decode ( $this->htmlValue (), ENT_QUOTES, 'utf-8' );
		return "<p class=\"info\">" . "<strong>{$value}</strong>" . "</p>\n";
	}
	// }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
