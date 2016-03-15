<?php

/**
 * @file    single.php
 * @brief   single input element
 *
 * @author Frank Hellenkamp <jonas@depage.net>
 * @author Sebastian Reinhold <sebastian@bitbernd.de>
 **/
namespace Core\Htmlform\Elements;

/** 
 * @brief HTML-single-choice input type i.e. radio and select.
 *
 * Class for radio-like HTML elements. Has the same return value, regardless
 * of skin type (radio or select).
 *
 * @section usage
 *
 * @code
 * <?php/**
 * @brief HTML-single-choice input type i.e.
 * radio and select.
 *
 * Class for radio-like HTML elements. Has the same return value, regardless
 * of skin type (radio or select).
 *
 * @section usage
 *
 * @code
 * <?php
 * $form = new depage\htmlform\htmlform('myform');
 *
 * // add single-element (radio is the default skin)
 * $form->addSingle('listOne', array(
 * 'label' => 'Language',
 * 'list' => array(
 * 'en' => 'English',
 * 'es' => 'Spanish',
 * 'fr' => 'French',
 * ),
 * ));
 *
 * // add a single-element with select-skin
 * $form->addSingle('listTwo', array(
 * 'label' => 'Language',
 * 'skin' => 'select',
 * 'list' => array(
 * 'en' => 'English',
 * 'es' => 'Spanish',
 * 'fr' => 'French',
 * ),
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
class Movable extends \Core\Htmlform\Abstracts\Input {

	// {{{ variables
	/**
	 * @brief Contains list of selectable options.
	 */
	protected $list = [];
	// }}}
	
	use Traits\InputConvertion;
	
	// {{{ __construct()
	/**
	 * @brief single class constructor
	 *
	 * @param $name (string)
	 *        	element name
	 * @param $parameters (array)
	 *        	element parameters, HTML attributes, validator specs etc.
	 * @param $form (object)
	 *        	parent form object
	 * @return void
	 *
	 */
	public function __construct($name, $parameters, $form) {
		parent::__construct($name, $parameters, $form);

		$this->list = isset($parameters['list']) && is_array ($parameters['list']) ? $parameters['list'] : [];
		$this->value = isset($parameters['value']) && is_array($parameters['value']) ? $parameters['value'] : NULL;
	}
	// }}}
	
	// {{{ setDefaults()
	/**
	 * @brief collects initial values across subclasses
	 *
	 * The constructor loops through these and creates settable class
	 * attributes at runtime. It's a compact mechanism for initialising
	 * a lot of variables.
	 *
	 * @return void
	 *
	 */
	protected function setDefaults() {
		parent::setDefaults();
		
		// single-choice-elements have values of type string
		$this->defaults ['defaultValue'] = '';
		$this->defaults ['skin'] = 'radio';
	}
	// }}}
	
	// {{{ htmlList()
	/**
	 * @brief Renders HTML - option list part of select/radio single element
	 *
	 * Works recursively in case of select-optgroups. If no parameters are
	 * parsed, it uses the list attribute of this element.
	 *
	 * @param $options (array)
	 *        	list elements and subgroups
	 * @param $value (string)
	 *        	value to be marked as selected
	 * @return $list (string) options-part of the HTML-select-element
	 *        
	 */
	protected function htmlList($options = null, $value = null) {
		if ($value == null)
			$value = $this->htmlValue();

		if ($options == null)
			$options = $this->list;
		
		$options = $this->htmlEscape ( $options );
		$list = '';
		$list_selected = '';
		
		if(!is_array($value)) { $value = array($value); }
		
		foreach ( $options as $index => $option ) {
			if (is_array ( $option )) {
				$list .= "<optgroup label=\"{$index}\">" . $this->htmlList ( $option, $value ) . "</optgroup>";
			} else {
				if(in_array(( string ) $index, $value)) {
					$list_selected .= "<option value=\"{$index}\">{$option}</option>";
				} else {
					$list .= "<option value=\"{$index}\">{$option}</option>";
				}
			}
		}
		
		return array($list, $list_selected);
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
		$marker = $this->htmlMarker();
		$label = $this->htmlLabel();
		$lists = $this->htmlList();
		$wrapperAttributes = $this->htmlWrapperAttributes ();
		$errorMessage = $this->htmlErrorMessage ();
		$helpMessage = $this->htmlHelpMessage ();
		$this->jsRender();
		
		// render HTML select
		$avaliableAttributes = $this->htmlInputAttributes([ 'required' ]);
		$enabledAttributes = $this->htmlInputAttributes();
		
		$return = "<div {$wrapperAttributes}><p class=\"select-movable from-move\">" . "<label>" . "<span class=\"label\">{$label}{$marker}</span>" . "<select multiple=\"multiple\" size=\"10\" {$avaliableAttributes}>{$lists[0]}</select>" . "</label></p>\n";
		$return .= "<p class=\"buttons\">";
		$return .= '<button class="button button-enable">&raquo;</button>';
		$return .= '<br />';
		$return .= '<button class="button button-disable">&laquo;</button>';
		$return .= "</p>";
		$return .= "<p class=\"select-movable to-move\">" . "<label>" . "<span class=\"label\">{$label}{$marker}</span>" . "<select multiple=\"multiple\" size=\"10\" name=\"{$this->name}[]\"{$enabledAttributes}>{$lists[1]}</select>" . "</label>" . $errorMessage . $helpMessage . "</p>\n";
		$return .= "</div>";
		return $return;
	}
	// }}}
	
	// {{{ typeCastValue()
	/**
	 * @brief Converts value to element specific type.
	 *
	 * @return void
	 *
	 */
	protected function typeCastValue() {
		$this->value = !is_array($this->value) ? array($this->value) : $this->value;
	}
	// }}}
	
	protected function jsRender() {
		$document = \Core\Base\Action::getComponent('document');
		ob_start();
		?>

		$(document).ready(function() {
			$('.input-movable').parents('form').submit(function() {
				$('.input-movable .to-move select option').attr('selected',true);
			}).each(function() {
				$(this).find('button.button-enable').click(function() {
					movable_move($(this).parent().closest("div").find(".from-move select"), $(this).parent().closest("div").find(".to-move select"));
					return false;
				});

				$(this).find('button.button-disable').click(function() {
					movable_move($(this).parent().closest("div").find(".to-move select"), $(this).parent().closest("div").find(".from-move select"));
					return false;
				});

				$(this).find('.from-move select').dblclick(function() {
					movable_move($(this).parent().closest("div").find(".from-move select"), $(this).parent().closest("div").find(".to-move select"));
					return false;
				});

				$(this).find('.to-move select').dblclick(function() {
					movable_move($(this).parent().closest("div").find(".to-move select"), $(this).parent().closest("div").find(".from-move select"));
					return false;
				});
			});
			
			$.each($(".to-move select"), function() {
				this.addEventListener("blur", function() {
					$(this).find("option").attr("selected", true);
					this.checkValidity();
				}, false);

				this.addEventListener("invalid", function() {
					$(this).find("option").attr("selected", true);
				}, false);
			});
		});
		
		function movable_move(from, to) {
			var selection = $(from).find("option:selected");

			if(!selection.length)
				return;

			selection.remove().appendTo($(to));
		}
		
		<?php $document->addScript('.movable-select', ob_get_clean());
	}

	public function htmlInputAttributes($exclude = [])
	{
		$result = parent::htmlInputAttributes();

		if(is_array($exclude) && !empty($exclude))
			foreach($exclude as $e)
				$result = preg_replace("#(\b)+$e(?:(?:\b)+|\=(?:'|\").*?(?:'|\"))?#", '\1', $result);

		return $result;
	}
	
}