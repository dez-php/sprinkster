<?php

namespace Core;

class Document {
	
	const POS_HEAD=0;
	const POS_BEGIN=1;
	const POS_END=2;
	const POS_LOAD=3;
	const POS_READY=4;
	
	private static $_instance;
	
	private $linkTags = array();	
	private $metaTags = array();		
	
	private $hasScripts=false;
	private $_baseUrl;
	public $enableJavaScript=true;
	public $scriptMap=array();
	
	public $packages=array();
	public $corePackages;
	
	protected $css = array();
	protected $cssFiles=array();
	
	protected $scripts = array();
	protected $scriptFiles=array();
	protected $coreScripts=array();
	
	protected $title = '';

	public $coreScriptPosition=self::POS_HEAD;
	public $defaultScriptFilePosition=self::POS_HEAD;
	public $defaultScriptPosition=self::POS_READY;
	
	/**
	 * @param array $options
	 * @return \Core\Http\Request
	 */
	public static function getInstance() {
		if(self::$_instance == null) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	public function reset()
	{
		$this->hasScripts=false;
		$this->coreScripts=array();
		$this->cssFiles=array();
		$this->css=array();
		$this->scriptFiles=array();
		$this->scripts=array();
		$this->metaTags=array();
		$this->linkTags=array();
	}
	
	public function resetMeta()
	{
		$this->metaTags=array();
		return $this;
	}
	
	public function resetLink()
	{
		$this->linkTags=array();
		return $this;
	}
	
	public function render(&$output)
	{
		if(!$this->hasScripts) {
			return;
		}
		$this->renderCoreScripts();
		if(!empty($this->scriptMap)) {
			$this->remapScripts();
		}
		$this->unifyScripts();
		$this->renderHead($output);
		if($this->enableJavaScript)
		{
			$this->renderBodyBegin($output);
			$this->renderBodyEnd($output);
		}
	}

	protected function unifyScripts()
	{
		if(!$this->enableJavaScript) {
			return;
		}
		$map=array();
		if(isset($this->scriptFiles[self::POS_HEAD])) {
			$map=$this->scriptFiles[self::POS_HEAD];
		}
		if(isset($this->scriptFiles[self::POS_BEGIN]))
		{
			foreach($this->scriptFiles[self::POS_BEGIN] as $scriptFile=>$scriptFileValue)
			{
				if(isset($map[$scriptFile])) {
					unset($this->scriptFiles[self::POS_BEGIN][$scriptFile]);
				} else {
					$map[$scriptFile]=true;
				}
			}
		}
		if(isset($this->scriptFiles[self::POS_END]))
		{
			foreach($this->scriptFiles[self::POS_END] as $key=>$scriptFile)
			{
				if(isset($map[$key])) {
					unset($this->scriptFiles[self::POS_END][$key]);
				}
			}
		}
	}
	
	protected function remapScripts()
	{
		$cssFiles=array();
		foreach($this->cssFiles as $url=>$media) {
			$name=basename($url);
			if(isset($this->scriptMap[$name])) {
				if($this->scriptMap[$name]!==false) {
					$cssFiles[$this->scriptMap[$name]]=$media;
				}
			}
			elseif(isset($this->scriptMap['*.css'])) {
				if($this->scriptMap['*.css']!==false) {
					$cssFiles[$this->scriptMap['*.css']]=$media;
				}
			}
			else {
				$cssFiles[$url]=$media;
			}
		}
		$this->cssFiles=$cssFiles;
		$jsFiles=array();
		foreach($this->scriptFiles as $position=>$scriptFiles)
		{
			$jsFiles[$position]=array();
			foreach($scriptFiles as $scriptFile=>$scriptFileValue)
			{
				$name=basename($scriptFile);
				if(isset($this->scriptMap[$name]))
				{
					if($this->scriptMap[$name]!==false) {
						$jsFiles[$position][$this->scriptMap[$name]]=$this->scriptMap[$name];
					}
				}
				elseif(isset($this->scriptMap['*.js']))
				{
					if($this->scriptMap['*.js']!==false) {
						$jsFiles[$position][$this->scriptMap['*.js']]=$this->scriptMap['*.js'];
					}
				}
				else
					$jsFiles[$position][$scriptFile]=$scriptFileValue;
			}
		}
		$this->scriptFiles=$jsFiles;
	}
	
	public function renderBodyEnd(&$output)
	{
		if(!isset($this->scriptFiles[self::POS_END]) && !isset($this->scripts[self::POS_END]) && !isset($this->scripts[self::POS_READY]) && !isset($this->scripts[self::POS_LOAD])) {
			return;
		}
		$fullPage=0;
		$output=preg_replace('/(<\\/body\s*>)/is','<###end###>$1',$output,1,$fullPage);
		$html='';
		if(isset($this->scriptFiles[self::POS_END]))
		{
			foreach($this->scriptFiles[self::POS_END] as $scriptFileUrl=>$scriptFileValue)
			{
				if(is_array($scriptFileValue)) {
					$html.=\Core\Html::scriptFile($scriptFileUrl,$scriptFileValue)."\n";
				} else {
					$html.=\Core\Html::scriptFile($scriptFileUrl)."\n";
				}
			}
		}
		$scripts=isset($this->scripts[self::POS_END]) ? $this->scripts[self::POS_END] : array();
		if(isset($this->scripts[self::POS_READY]))
		{
			if($fullPage) {
				$scripts[]="jQuery(function($) {\n".implode("\n",$this->scripts[self::POS_READY])."\n});";
			} else {
				$scripts[]=implode("\n",$this->scripts[self::POS_READY]);
			}
		}
		if(isset($this->scripts[self::POS_LOAD]))
		{
			if($fullPage) {
				$scripts[]="jQuery(window).on('load',function() {\n".implode("\n",$this->scripts[self::POS_LOAD])."\n});";
			} else {
				$scripts[]=implode("\n",$this->scripts[self::POS_LOAD]);
			}
		}
		if(!empty($scripts)) {
			$html.=$this->renderScriptBatch($scripts);
		}
		if($fullPage) {
			$output=str_replace('<###end###>',$html,$output);
		} else {
			$output=$output.$html;
		}
	}
	
	public function renderBodyBegin(&$output)
	{
		$html='';
		if(isset($this->scriptFiles[self::POS_BEGIN]))
		{
			foreach($this->scriptFiles[self::POS_BEGIN] as $scriptFileUrl=>$scriptFileValue)
			{
				if(is_array($scriptFileValue)) {
					$html.=\Core\Html::scriptFile($scriptFileUrl,$scriptFileValue)."\n";
				} else { 
					$html.=\Core\Html::scriptFile($scriptFileUrl)."\n";
				}
			}
		}
		if(isset($this->scripts[self::POS_BEGIN])) {
			$html.=$this->renderScriptBatch($this->scripts[self::POS_BEGIN]);
		}
		if($html!=='')
		{
			$count=0;
			$output=preg_replace('/(<body\b[^>]*>)/is','$1<###begin###>',$output,1,$count);
			if($count) {
				$output=str_replace('<###begin###>',$html,$output);
			} else {
				$output=$html.$output;
			}
		}
	}
	
	public function renderHead(&$output)
	{
		$html = $this->getTitle() ? (\Core\Html::tag('title',array(), $this->getTitle()) . "\n") : '';
		foreach($this->metaTags as $meta) {
			$html.=\Core\Html::metaTag($meta['content'],null,null,$meta)."\n";
		}
		foreach($this->linkTags as $link) {
			$html.=\Core\Html::linkTag(null,null,null,null,$link)."\n";
		}
		foreach($this->cssFiles as $url=>$media) {
			$html.=\Core\Html::cssFile($url,$media)."\n";
		}
		foreach($this->css as $css) {
			$html.=\Core\Html::css($css[0],$css[1])."\n";
		}
		if($this->enableJavaScript)
		{ 
			if(isset($this->scriptFiles[self::POS_HEAD]))
			{
				foreach($this->scriptFiles[self::POS_HEAD] as $scriptFileValueUrl=>$scriptFileValue)
				{
					if(is_array($scriptFileValue)) {
						if(isset($scriptFileValue['check']) && $scriptFileValue['check'] && isset($scriptFileValue['file']) && $scriptFileValue['file']) {
							$check = $scriptFileValue['check'];
							unset($scriptFileValue['check'], $scriptFileValue['file']);
							$file = str_replace(['<','>'],['%3C','%3E'],\Core\Html::scriptFile($scriptFileValueUrl,$scriptFileValue));
							$html .= \Core\Html::script('if(typeof ' . $check . ' == "undefined") { document.write(unescape(\'' . $file . '\')); } ')."\n";
						} else {
							$html.=\Core\Html::scriptFile($scriptFileValueUrl,$scriptFileValue)."\n";
						}
					} else {
						$html.=\Core\Html::scriptFile($scriptFileValueUrl)."\n";
					}
				}
			} 
			if(isset($this->scripts[self::POS_HEAD])) {
				$html.=$this->renderScriptBatch($this->scripts[self::POS_HEAD]);
			}
		}
		if($html!=='')
		{
			$count=0;
			$output=preg_replace('/(<title\b[^>]*>|<\\/head\s*>)/is','<###head###>$1',$output,1,$count);
			if($count) {
				$output=str_replace('<###head###>',$html,$output);
			} else {
				$output=$html.$output;
			}
		}
	}
	
	protected function renderScriptBatch(array $scripts)
	{
		$html = '';
		$scriptBatches = array();
		foreach($scripts as $scriptValue)
		{
			if(is_array($scriptValue))
			{
				$scriptContent = $scriptValue['content'];
				unset($scriptValue['content']);
				$scriptHtmlOptions = $scriptValue;
			}
			else
			{
				$scriptContent = $scriptValue;
				$scriptHtmlOptions = array();
			}
			$key=serialize(ksort($scriptHtmlOptions));
			$scriptBatches[$key]['htmlOptions']=$scriptHtmlOptions;
			$scriptBatches[$key]['scripts'][]=$scriptContent;
		}
		foreach($scriptBatches as $scriptBatch) {
			if(!empty($scriptBatch['scripts'])) {
				$html.=\Core\Html::script(implode("\n",$scriptBatch['scripts']),$scriptBatch['htmlOptions'])."\n";
			}
		}
		return $html;
	}
	
	public function renderCoreScripts()
	{
		if($this->coreScripts===null) {
			return;
		}
		$cssFiles=array();
		$jsFiles=array();
		foreach($this->coreScripts as $name=>$package) {
			if($package) {
				$baseUrl=$this->getPackageBaseUrl($name);
				if(!empty($package['js'])) {
					foreach($package['js'] as $check => $js) {
						if(!preg_match('~^([0-9],{1,})$~', $check)) {
							$jsFiles[$baseUrl.'/'.$js]=['file' => $baseUrl.'/'.$js,'check' => $check];
						} else {
							$jsFiles[$baseUrl.'/'.$js]=$baseUrl.'/'.$js;
						}
					}
				}
				if(!empty($package['css'])) {
					foreach($package['css'] as $css) {
						$cssFiles[$baseUrl.'/'.$css]='';
					}
				}
			}
		}
		// merge in place
		if($cssFiles!==array()) {
			foreach($this->cssFiles as $cssFile=>$media) {
				$cssFiles[$cssFile]=$media;
			}
			$this->cssFiles=$cssFiles;
		} 
		if($jsFiles!==array()) {
			if(isset($this->scriptFiles[$this->coreScriptPosition])) {
				foreach($this->scriptFiles[$this->coreScriptPosition] as $url => $value) {
					$jsFiles[$url]=$value;
				}
			}
			$this->scriptFiles[$this->coreScriptPosition]=$jsFiles;
		}
	}
	
	public function getPackageBaseUrl($name)
	{
		if(!isset($this->coreScripts[$name])) {
			return false;
		}
		$package=$this->coreScripts[$name];
		if(isset($package['baseUrl'])) {
			$baseUrl=$package['baseUrl'];
			if($baseUrl==='' || $baseUrl[0]!=='/' && strpos($baseUrl,'://')===false) {
				$baseUrl=\Core\Http\Request::getInstance()->getBaseUrl().'/'.$baseUrl;
			}
			$baseUrl=rtrim($baseUrl,'/');
		} elseif(isset($package['basePath'])) {
			$baseUrl=\Core\Base\Action::getComponent('AssetManager')->publish(\Core\Alias::getPathOfAlias($package['basePath']));
		} else {
			$baseUrl=$this->getCoreScriptUrl();
		}
		return $this->coreScripts[$name]['baseUrl']=$baseUrl;
	}
	
	public function getCoreScriptUrl()
	{
		if($this->_baseUrl!==null) {
			return $this->_baseUrl;
		} else {
			return \Core\Base\Action::getComponent('AssetManager')->publish(\Core\Base\Init::getFMBase() . '/Web/Js/Source');
		}
	}
	
	public function addMetaTag($content,$name=null,$httpEquiv=null,$options=array(),$id=null)
	{
		$this->hasScripts=true;
		if($name!==null) { $options['name']=$name; }
		if($httpEquiv!==null) { $options['http-equiv']=$httpEquiv; }
		$options['content']=$content;
		$this->metaTags[null===$id?count($this->metaTags):$id]=$options;
		return $this;
	}
	
	public function addLink($relation=null,$type=null,$href=null,$media=null,$options=array()) {
		$this->hasScripts=true;
		if($relation!==null) { $options['rel']=$relation; }
		if($type!==null) { $options['type']=$type; }
		if($href!==null) { $options['href']=$href; }
		if($media!==null) { $options['media']=$media; }
		$this->linkTags[serialize($options)]=$options;
		return $this;		
	}
	
	public function getLinks() {
		return $this->links;
	}	
	
	public function addTitle($title) {
		$this->title = $title;
		return $this;
	}
	
	public function getTitle() {
		return $this->title;
	}
	
	public function addCssFile($url,$media='') {
		$this->hasScripts=true;
		$this->cssFiles[$url]=$media;
		return $this;
	}
	
	public function addCss($id,$css,$media='') {
		$this->hasScripts=true;
		$this->css[$id]=array($css,$media);
		return $this;
	}
	
	public function addScriptFile($url,$position=null,array $htmlOptions=array()) {
		if($position===null) {
			$position=$this->defaultScriptFilePosition;
		}
		$this->hasScripts=true;
		if(empty($htmlOptions)) {
			$value=$url;
		} else {
			$value=$htmlOptions;
			$value['src']=$url;
		}
		$this->scriptFiles[$position][$url]=$value;
		return $this;			
	}
	
	public function addScript($id,$script,$position=null,array $htmlOptions=array())
	{
		if($position===null) {
			$position=$this->defaultScriptPosition;
		}
		$this->hasScripts=true;
		if(empty($htmlOptions)) {
			$scriptValue=$script;
		} else {
			if($position==self::POS_LOAD || $position==self::POS_READY) {
				throw new \Core\Exception('Script HTML options are not allowed for "\Core\Document::POS_LOAD" and "\Core\Document::POS_READY".');
			}
			$scriptValue=$htmlOptions;
			$scriptValue['content']=$script;
		}
		$this->scripts[$position][$id]=$scriptValue;
		if($position===self::POS_READY || $position===self::POS_LOAD) {
			$this->addCoreScript('jquery');
		}
		return $this;
	}
	
	public function alreadyLoadedCoreScript($name) {
		$this->coreScripts[$name] = false;
	}
	
	public function addCoreScript($name)
	{
		if(isset($this->coreScripts[$name])) {
			return $this;
		}
		if(isset($this->packages[$name])) {
			$package=$this->packages[$name];
		} else {
			if($this->corePackages===null && file_exists(__DIR__.'/Web/Js/Packages.php')) {
				$this->corePackages=require(__DIR__.'/Web/Js/Packages.php');
			} 
			if(isset($this->corePackages[$name])) {
				$package=$this->corePackages[$name];
			}
		}
		if(isset($package)) {
			if(!empty($package['depends']))
			{
				foreach($package['depends'] as $p)
					$this->addCoreScript($p);
			}
			$this->coreScripts[$name]=$package;
			$this->hasScripts=true;
		}
		return $this;
	}
	
	public function addPackage($name,$definition) {
		$this->packages[$name]=$definition;
		return $this;
	}
}
?>