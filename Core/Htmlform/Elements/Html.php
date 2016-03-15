<?php

/**
 * @file    html.php
 * @brief   html element
 *
 * @author Frank Hellenkamp <jonas@depage.net>
 * @author Sebastian Reinhold <sebastian@bitbernd.de>
 **/
namespace Core\Htmlform\Elements;

/**
 * @brief Can be used to insert custom HTML between rendered HTML elements.
 *
 * Class for custom HTML code sections.
 *
 * @section usage
 *
 * @code
 * <?php/**
 * @brief Can be used to insert custom HTML between rendered HTML elements.
 *
 * Class for custom HTML code sections.
 *
 * @section usage
 *
 * @code
 * <?php
 * $form = new depage\htmlform\htmlform('myform');
 *
 * // add HTML
 * $form->addHtml('<div id="myimage"></div>');
 *
 * // process form
 * $form->process();
 *
 * // Display the form.
 * echo ($form);
 * ?>
 * @endcode
 */
class Html {
	// {{{ variables
	/**
	 * @brief HTML code to be printed
	 */
	private $htmlString;
	// }}}
	
	// {{{ __construct()
	/**
	 * @brief html class constructor
	 *
	 * @param $htmlString (string)
	 *        	HTML to be printed
	 *        	
	 */
	public function __construct($htmlString) {
		$this->htmlString = $htmlString;
	}
	// }}}
	
	// {{{ __toString()
	/**
	 * @brief Renders element to HTML.
	 *
	 * @return $this->htmlString (string) HTML-rendered element
	 *        
	 */
	public function __toString() {
		return ( string ) $this->htmlString;
	}
	// }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
