<?php

/**
 * @file    elements/range.php
 * @brief   range input element
 *
 * @author Frank Hellenkamp <jonas@depage.net>
 * @author Sebastian Reinhold <sebastian@bitbernd.de>
 **/
namespace Core\Htmlform\Elements;

/**
 * @brief HTML range input type.
 *
 * Class for HTML5 input type "range".
 *
 * @section usage
 *
 * @code
 * <?php/**
 * @brief HTML range input type.
 *
 * Class for HTML5 input type "range".
 *
 * @section usage
 *
 * @code
 * <?php
 * $form = new depage\htmlform\htmlform('myform');
 *
 * // add a range element
 * $form->addNumber('stars', array(
 * 'label' => 'Please rate this item!',
 * 'defaultValue' => 3,
 * 'min' => 1,
 * 'max' => 5,
 * 'step' => 1,
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
class Range extends \Core\Htmlform\Elements\Number {
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
