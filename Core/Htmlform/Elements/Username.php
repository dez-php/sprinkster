<?php

/**
 * @file    elements/email.php
 * @brief   email input element
 *
 * @author Frank Hellenkamp <jonas@depage.net>
 * @author Sebastian Reinhold <sebastian@bitbernd.de>
 **/
namespace Core\Htmlform\Elements;

/**
 * @brief HTML email input type.
 *
 * Class for the HTML5 input-type "email".
 *
 * @section usage
 *
 * @code
 * <?php/**
 * @brief HTML email input type.
 *
 * Class for the HTML5 input-type "email".
 *
 * @section usage
 *
 * @code
 * <?php
 * $form = new depage\htmlform\htmlform('myform');
 *
 * // add a required email field
 * $form->addEmail('email', array(
 * 'label' => 'Email address',
 * 'required' => true,
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
class Username extends \Core\Htmlform\Elements\Text {
	// {{{ setDefaults()
	/**
	 * @brief collects initial values across subclasses.
	 *
	 * The constructor loops through these and creates settable class
	 * attributes at runtime. It's a compact mechanism for initialising
	 * a lot of variables.
	 *
	 * @return void
	 *
	 */
	protected function setDefaults() {
		parent::setDefaults ();
		$this->defaults ['errorMessage'] = ('Username must contain only a-z 0-9 _ .');
		
		// @todo add option to test mail domain name (dns)
	}
	// }}}
	
	// {{{ __toString()
	/**
	 * @brief Renders element to HTML.
	 *
	 * @return (string) HTML rendered element
	 *        
	 */
	public function __toString() {
		$value = $this->htmlValue ();
		$inputAttributes = $this->htmlInputAttributes ();
		$marker = $this->htmlMarker ();
		$label = $this->htmlLabel ();
		$wrapperAttributes = $this->htmlWrapperAttributes ();
		$errorMessage = $this->htmlErrorMessage ();
		$list = $this->htmlList ();
		$helpMessage = $this->htmlHelpMessage ();
		
		return "<p {$wrapperAttributes}>" . "<label>" . "<span class=\"label\">{$label}{$marker}</span>" . "<input name=\"{$this->name}\" type=\"text\"{$inputAttributes} value=\"{$value}\">" . $list . "</label>" . $errorMessage . $helpMessage . "</p>\n";
	}
	// }}}
	
	// {{{ validate()
	/**
	 * @brief Validate the creditcard data
	 *
	 * @todo validate based on (?):
	 *       http://www-sst.informatik.tu-cottbus.de/~db/doc/Java/GalileoComputing-JavaInsel/java-04.htm#t321
	 * @todo validate specific to cardtype
	 *      
	 * @return (bool) validation result
	 *        
	 */
	public function validate() {
		return ( bool ) preg_match ( "/^[a-z0-9\_\.]+$/i", $this->value );
	}
	// }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
