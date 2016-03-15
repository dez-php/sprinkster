<?php

/**
 * @file    hidden.php
 * @brief   hidden input element
 *
 * @author Frank Hellenkamp <jonas@depage.net>
 * @author Sebastian Reinhold <sebastian@bitbernd.de>
 **/
namespace Core\Htmlform\Elements;

/**
 * @brief HTML hidden input type.
 *
 * Class for the HTML input-type "hidden".
 *
 * @section usage
 *
 * @code
 * <?php/**
 * @brief HTML hidden input type.
 *
 * Class for the HTML input-type "hidden".
 *
 * @section usage
 *
 * @code
 * <?php
 * $form = new depage\htmlform\htmlform('myform');
 *
 * // add a hidden field
 * $form->addImagemanager('nonce', array(
 * 'defaultValue' => 'XD5fkk',
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
class Filemanager extends \Core\Htmlform\Elements\Text {
	// {{{ __toString()
	/**
	 * @brief Renders element to HTML.
	 *
	 * @return (string) HTML-rendered element
	 *        
	 */
	public function __toString() {
		$formName = $this->htmlFormName ();
		$classes = $this->htmlClasses ();
		$value = $this->htmlValue ();
		$errorMessage = $this->htmlErrorMessage ();
		$helpMessage = $this->htmlHelpMessage ();
		$src_noimage = $src = '';
		if ($value && file_exists ( \Core\Base\Init::getBase() . '/uploads/data/' . $value )) {
			$src = \Core\Http\Request::getInstance()->getBaseUrl() . 'uploads/data/' . $value;
		}
		return "<p><input name=\"{$this->name}\" id=\"{$formName}-{$this->name}\" type=\"text\" class=\"{$classes}\" value=\"{$value}\" readonly=\"readonly\">\n" . "\n<br />" . "<a style=\"cursor: pointer;\" onclick=\"file_upload('{$formName}-{$this->name}');\">" . ('Browse') . "</a>" . " | <a style=\"cursor: pointer;\" onclick=\"$('#{$formName}-{$this->name}').val('');\">" . ('Clear') . "</a> " . $errorMessage . $helpMessage . "</p>";
	}
	// }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
