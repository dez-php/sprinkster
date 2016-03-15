<?php

namespace Core\Htmlform\Elements;

class Datetime extends \Core\Htmlform\Elements\Text {

	protected function setDefaults() {
		parent::setDefaults ();
		
		$this->defaults ['picker_options'] = [];
	}

	public function __toString()
	{
		$value = $this->htmlValue();
		$inputAttributes = $this->htmlInputAttributes();
		$marker = $this->htmlMarker();
		$label = $this->htmlLabel();
		$list = $this->htmlList();
		$wrapperAttributes = $this->htmlWrapperAttributes();
		$errorMessage = $this->htmlErrorMessage();
		$helpMessage = $this->htmlHelpMessage();
		$this->jsRender();
		
		return "<p {$wrapperAttributes}>" . "<label>" . "<span class=\"label\">{$label}{$marker}</span>" . "<input name=\"{$this->name}\" type=\"datetime\"{$inputAttributes} class=\"datepicker\" value=\"{$value}\">" . $list . "</label>" . $errorMessage . $helpMessage . "</p>\n";
	}

	protected function jsRender()
	{ 
		$document = \Core\Base\Action::getComponent('document');
		ob_start();
		?>

		$(document).ready(function() {
			$("#<?php echo $this->formName.'-'.$this->getName();?> input.datepicker").datepicker(<?php echo $this->picker_options ? \Core\Javascript\Encode::encode($this->picker_options) : ''; ?>);
		});

		<?php
		$document->addScript('datetime-datepicker-' . $this->formName.'-'.$this->getName(), ob_get_clean());
	}
}
