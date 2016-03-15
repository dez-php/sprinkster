<?php

/**
 * @file    placeholderMultiple.php
 * @brief   adds a placeholder for an array element
 *
 * @author Frank Hellenkamp <jonas@depage.net>
 * @author Ben Wallis <benedict_wallis@yahoo.co.uk>
 **/
namespace Core\Htmlform\Elements;

/**
 * @brief Placeholder for form multiple form values.
 *
 * @section usage
 *
 * @code
 * <?php/**
 * @brief Placeholder for form multiple form values.
 *
 * @section usage
 *
 * @code
 * <?php
 * $form = new depage\htmlform\htmlform('myform');
 *
 * // add a hidden field
 * $form->addPlaceholderMutliple('nonce');
 *
 * ?>
 * @endcode
 */
class Placeholdermultiple extends \Core\Htmlform\Elements\Placeholder {
	// {{{ typeCastValue()
	/**
	 * @brief Casts to array.
	 *
	 * @return (array)
	 *
	 */
	public function typeCastValue() {
		return array ();
	}
	// }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
