<?php 

namespace Core\Web;

class Addthis extends \Core\Base\Widget
{
	// @var string the name of username on addThis.
	public $username;
	// @var string valid url of addThis script. Defaults to 'http://s7.addthis.com/js/250/addthis_widget.js'.
	public $scriptUrl='http://s7.addthis.com/js/250/addthis_widget.js';
	// @var string valid url of addThis default button. Defaults to 'http://www.addthis.com/bookmark.php?v=250'.
	public $linkUrl='http://www.addthis.com/bookmark.php?v=250';
	// @var string the caption of the addThis default button. Defaults to 'Share'.
	public $defaultButtonCaption='Share';
	// @var boolean whether the default addThis button is visible. Defaults to true.
	public $showDefaultButton=true;
	// @var boolean whether the default addThis button caption is visible. Defaults to true.
	public $showDefaultButtonCaption=true;
	// @var string the separator character. Defaults to '&nbsp;'.
	public $separator='&nbsp;';
	// @var array the addThis div tag attributes.
	public $htmlOptions=array();
	// @var array the addThis default button a tag attributes.
	public $linkOptions=array();
	// @var array the addThis services to show.
	public $showServices=array();
	// @var boolean whether the services name
	public $showServicesTitle=false;
	// @var array the addThis cofig parameters.
	public $config=array();
	// @var array the addThis share parameters.
	public $share=array();

	/**
	 * Run the addThis widget.
	 * This renders the body part of the assThis widget.
	*/
	public function result()
	{

		// Get this widget id.
		$id = $this->getId();

		// Set this widget id.
		$this->htmlOptions['id']=$id;
		// Set the default 'class' attribute of addThis 'div' otherwise add users custom 'class' attribute.
		empty($this->htmlOptions['class']) ? $this->htmlOptions['class']='addthis_toolbox addthis_default_style' : $this->htmlOptions['class']='addthis_toolbox ' . $this->htmlOptions['class'];
		// Open default addThis div tag with htmlOptions.
		echo \Core\Html::openTag('div', $this->htmlOptions) . "\n";
		// Open default addThis button if showDefaultButton is set to true.
		if ($this->showDefaultButton)
		{
			// Set the default addThis link url.
			$this->linkOptions['href']=$this->linkUrl;
			// Set the default 'class' attribute of addThis link otherwise set to user defined 'class'.
			empty($this->linkOptions['class']) ? $this->linkOptions['class']='addthis_button_compact' : '';
			// Print default addThis button link tag.
			echo \Core\Html::openTag('a', $this->linkOptions);
			// Print default addThis button caption if showDefaultButtonCaption is set to true.
			if ($this->showDefaultButtonCaption)
				echo $this->defaultButtonCaption;
			// Close default addThis button link tag.
			echo \Core\Html::closeTag('a') . "\n";
		}
		// Check what services to show.
		if(isset($this->showServices)){
			foreach ($this->showServices as $i)
			{
				if ($i != 'separator')
				{
					echo \Core\Html::openTag('a', array('class'=>"addthis_button_{$i}") );
					if ($this->showServicesTitle && $i != 'facebook_like')
						echo ucfirst("{$i}");
					echo \Core\Html::closeTag('a') . "\n";
				} else {
					echo \Core\Html::openTag('span', array('class'=>"addthis_{$i}") );
					echo "{$this->separator}";
					echo \Core\Html::closeTag('span') . "\n";
				}
			}
			// Destroy @var showServices.
			unset($this->showServices);
		}
		// Close default addThis div tag.
		echo \Core\Html::closeTag('div');
		echo '<!-- AddThis id:{$this->id}-->';
		// Register script file, addThis config and share if are set.
		$scriptFileUri=isset($this->username)?$scriptFileUri="{$this->scriptUrl}#username={$this->username}" : $scriptFileUri="{$this->scriptUrl}";
		\Core\BAse\Action::getComponent('document')->addScriptFile($scriptFileUri);
		// Check if addThis $config parametes are set if true place them.
		if (!empty($this->config))
		{
			$config = \Core\JavaScript\Encode::encode($this->config);
			\Core\BAse\Action::getComponent('document')->addScript(__CLASS__.'#'.$id, "var addthis_config={$config};", false);
		}
		// Destroy addThis #config parameters.
		unset($this->config);
		// Check if addThis $share parametes are set if true place them.
		if (!empty($this->share))
		{
			$share = \Core\JavaScript\Encode::encode($this->share);
			\Core\BAse\Action::getComponent('document')->addScript(__CLASS__.'#'.$id, "var addthis_share={$share};", false);
		}
		// Destroy addThis #share parameters.
		unset ($this->share);
	}
}