<?php

/**
 * @file    password.php
 * @brief   password input element
 *
 * @author Frank Hellenkamp <jonas@depage.net>
 * @author Sebastian Reinhold <sebastian@bitbernd.de>
 **/
namespace Core\Htmlform\Elements;

/** 
 * @brief HTML password input type.
 *
 * Class for the HTML input type "password". Entered characters are masked with
 * asterisks or bullets (depends on browser).
 *
 * @section usage
 *
 * @code
 * <?php/**
 * @brief HTML password input type.
 *
 * Class for the HTML input type "password". Entered characters are masked with
 * asterisks or bullets (depends on browser).
 *
 * @section usage
 *
 * @code
 * <?php
 * $form = new depage\htmlform\htmlform('myform');
 *
 * // add a password field
 * $form->addPassword('userPass', array(
 * 'label' => 'Password',
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
class Password extends \Core\Htmlform\Elements\Text {
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
