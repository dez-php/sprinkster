<?php

namespace Core\Form\Elements;

/**
 * @default validator for range input elements
 */
class Password extends \Core\Form\Elements\Text {
	
	/**
	 *
	 * @var null number
	 */
	protected $min = 3;
}
