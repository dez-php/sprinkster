<?php

namespace Core;

class Html {
	
	public static $liveEvents=true;
	
	const ID_PREFIX='jfm';
	
	private static $_modelNameConverter;
	/**
	 * @var string the CSS class for highlighting error inputs. Form inputs will be appended
	 * with this CSS class if they have input errors.
	 */
	public static $errorCss='error';
	
	/**
	 * @var boolean whether to render special attributes value. Defaults to true. Can be set to false for HTML5.
	 * @since 1.1.13
	 */
	public static $renderSpecialAttributesValue=true;
	
	/**
	 * @var boolean whether to close single tags. Defaults to true. Can be set to false for HTML5.
	 * @since 1.1.13
	 */
	public static $closeSingleTags=true;
	
	public static function encode($text)
	{
		return htmlspecialchars($text,ENT_QUOTES,'utf-8');
	}
	
	public static function getIdByName($name)
	{
		return str_replace(array('[]','][','[',']',' '),array('','_','_','','_'),$name);
	}
	
	public static function activeId($model,$attribute)
	{
		return self::getIdByName(self::activeName($model,$attribute));
	}
	
	public static function activeName($model,$attribute)
	{
		$a=$attribute; // because the attribute name may be changed by resolveName
		return self::resolveName($model,$a);
	}
	
	public static function resolveName($model,&$attribute)
	{
		$modelName=self::modelName($model);
		if(($pos=strpos($attribute,'['))!==false)
		{
			if($pos!==0)  // e.g. name[a][b]
				return $modelName.'['.substr($attribute,0,$pos).']'.substr($attribute,$pos);
			if(($pos=strrpos($attribute,']'))!==false && $pos!==strlen($attribute)-1)  // e.g. [a][b]name
			{
				$sub=substr($attribute,0,$pos+1);
				$attribute=substr($attribute,$pos+1);
				return $modelName.$sub.'['.$attribute.']';
			}
			if(preg_match('/\](\w+\[.*)$/',$attribute,$matches))
			{
				$name=$modelName.'['.str_replace(']','][',trim(strtr($attribute,array(']['=>']','['=>']')),']')).']';
				$attribute=$matches[1];
				return $name;
			}
		}
		return $modelName.'['.$attribute.']';
	}
	
	public static function modelName($model)
	{
		if(is_callable(self::$_modelNameConverter))
			return call_user_func(self::$_modelNameConverter,$model);
		$className=is_object($model) ? get_class($model) : (string)$model;
		return trim(str_replace('\\','_',$className),'_');
	}


	/**
	 * Generates an HTML element.
	 * @param string $tag the tag name
	 * @param array $htmlOptions the element attributes. The values will be HTML-encoded using {@link encode()}.
	 * If an 'encode' attribute is given and its value is false,
	 * the rest of the attribute values will NOT be HTML-encoded.
	 * Since version 1.1.5, attributes whose value is null will not be rendered.
	 * @param mixed $content the content to be enclosed between open and close element tags. It will not be HTML-encoded.
	 * If false, it means there is no body content.
	 * @param boolean $closeTag whether to generate the close tag.
	 * @return string the generated HTML element tag
	 */
	public static function tag($tag,$htmlOptions=array(),$content=false,$closeTag=true)
	{
		$html='<' . $tag . self::renderAttributes($htmlOptions);
		if($content===false)
			return $closeTag && self::$closeSingleTags ? $html.' />' : $html.'>';
		else
			return $closeTag ? $html.'>'.$content.'</'.$tag.'>' : $html.'>'.$content;
	}


	/**
	 * Renders the HTML tag attributes.
	 * Since version 1.1.5, attributes whose value is null will not be rendered.
	 * Special attributes, such as 'checked', 'disabled', 'readonly', will be rendered
	 * properly based on their corresponding boolean value.
	 * @param array $htmlOptions attributes to be rendered
	 * @return string the rendering result
	 */
	public static function renderDataAttributes($htmlOptions) {
		return self::renderAttributes($htmlOptions, 'data-');
	}
	
	/**
	 * Renders the HTML tag attributes.
	 * Since version 1.1.5, attributes whose value is null will not be rendered.
	 * Special attributes, such as 'checked', 'disabled', 'readonly', will be rendered
	 * properly based on their corresponding boolean value.
	 * @param array $htmlOptions attributes to be rendered
	 * @return string the rendering result
	 */
	public static function renderAttributes($htmlOptions, $prefix = null)
	{
		static $specialAttributes=array(
			'async'=>1,
			'autofocus'=>1,
			'autoplay'=>1,
			'checked'=>1,
			'controls'=>1,
			'declare'=>1,
			'default'=>1,
			'defer'=>1,
			'disabled'=>1,
			'formnovalidate'=>1,
			'hidden'=>1,
			'ismap'=>1,
			'loop'=>1,
			'multiple'=>1,
			'muted'=>1,
			'nohref'=>1,
			'noresize'=>1,
			'novalidate'=>1,
			'open'=>1,
			'readonly'=>1,
			'required'=>1,
			'reversed'=>1,
			'scoped'=>1,
			'seamless'=>1,
			'selected'=>1,
			'typemustmatch'=>1,
		);

		if($htmlOptions===array())
			return '';

		$html='';
		if(isset($htmlOptions['encode']))
		{
			$raw=!$htmlOptions['encode'];
			unset($htmlOptions['encode']);
		}
		else
			$raw=false;

		foreach($htmlOptions as $name=>$value)
		{
			if(isset($specialAttributes[$name]))
			{
				if($value)
				{
					$html .= ' ' . $name;
					if(self::$renderSpecialAttributesValue)
						$html .= '="' . $name . '"';
				}
			}
			elseif($value!==null)
				$html .= ' ' . $prefix . $name . '="' . ($raw ? $value : self::encode($value)) . '"';
		}

		return $html;
	}

	/**
	 * Generates a meta tag that can be inserted in the head section of HTML page.
	 * @param string $content content attribute of the meta tag
	 * @param string $name name attribute of the meta tag. If null, the attribute will not be generated
	 * @param string $httpEquiv http-equiv attribute of the meta tag. If null, the attribute will not be generated
	 * @param array $options other options in name-value pairs (e.g. 'scheme', 'lang')
	 * @return string the generated meta tag
	 */
	public static function metaTag($content,$name=null,$httpEquiv=null,$options=array())
	{
		if($name!==null)
			$options['name']=$name;
		if($httpEquiv!==null)
			$options['http-equiv']=$httpEquiv;
		$options['content']=$content;
		return self::tag('meta',$options);
	}

	/**
	 * Generates a link tag that can be inserted in the head section of HTML page.
	 * Do not confuse this method with {@link link()}. The latter generates a hyperlink.
	 * @param string $relation rel attribute of the link tag. If null, the attribute will not be generated.
	 * @param string $type type attribute of the link tag. If null, the attribute will not be generated.
	 * @param string $href href attribute of the link tag. If null, the attribute will not be generated.
	 * @param string $media media attribute of the link tag. If null, the attribute will not be generated.
	 * @param array $options other options in name-value pairs
	 * @return string the generated link tag
	 */
	public static function linkTag($relation=null,$type=null,$href=null,$media=null,$options=array())
	{
		if($relation!==null)
			$options['rel']=$relation;
		if($type!==null)
			$options['type']=$type;
		if($href!==null)
			$options['href']=$href;
		if($media!==null)
			$options['media']=$media;
		return self::tag('link',$options);
	}

	/**
	 * Links to the specified CSS file.
	 * @param string $url the CSS URL
	 * @param string $media the media that this CSS should apply to.
	 * @return string the CSS link.
	 */
	public static function cssFile($url,$media='')
	{
		return self::linkTag('stylesheet','text/css',$url,$media!=='' ? $media : null);
	}

	/**
	 * Encloses the given JavaScript within a script tag.
	 * @param string $text the JavaScript to be enclosed
	 * @param array $htmlOptions additional HTML attributes (see {@link tag})
	 * @return string the enclosed JavaScript
	 */
	public static function script($text,array $htmlOptions=array())
	{
		$defaultHtmlOptions=array(
			'type'=>'text/javascript',
		);
		$htmlOptions=array_merge($defaultHtmlOptions,$htmlOptions);
		return self::tag('script',$htmlOptions,"\n/*<![CDATA[*/\n{$text}\n/*]]>*/\n");
	}

	/**
	 * Encloses the given CSS content with a CSS tag.
	 * @param string $text the CSS content
	 * @param string $media the media that this CSS should apply to.
	 * @return string the CSS properly enclosed
	 */
	public static function css($text,$media='')
	{
		if($media!=='')
			$media=' media="'.$media.'"';
		return "<style type=\"text/css\"{$media}>\n/*<![CDATA[*/\n{$text}\n/*]]>*/\n</style>";
	}

	/**
	 * Includes a JavaScript file.
	 * @param string $url URL for the JavaScript file
	 * @param array $htmlOptions additional HTML attributes (see {@link tag})
	 * @return string the JavaScript file tag
	 */
	public static function scriptFile($url,array $htmlOptions=array())
	{
		$defaultHtmlOptions=array(
			'type'=>'text/javascript',
			'src'=>$url
		);
		$htmlOptions=array_merge($defaultHtmlOptions,$htmlOptions);
		return self::tag('script',$htmlOptions,'');
	}

	/**
	 * Generates an image tag.
	 * @param string $src the image URL
	 * @param string $alt the alternative text display
	 * @param array $htmlOptions additional HTML attributes (see {@link tag}).
	 * @return string the generated image tag
	 */
	public static function image($src,$alt='',$htmlOptions=array())
	{
		$htmlOptions['src']=$src;
		$htmlOptions['alt']=$alt;
		return self::tag('img',$htmlOptions);
	}

	/**
	 * Generates an open HTML element.
	 * @param string $tag the tag name
	 * @param array $htmlOptions the element attributes. The values will be HTML-encoded using {@link encode()}.
	 * If an 'encode' attribute is given and its value is false,
	 * the rest of the attribute values will NOT be HTML-encoded.
	 * Since version 1.1.5, attributes whose value is null will not be rendered.
	 * @return string the generated HTML element tag
	 */
	public static function openTag($tag,$htmlOptions=array())
	{
		return '<' . $tag . self::renderAttributes($htmlOptions) . '>';
	}

	/**
	 * Generates a close HTML element.
	 * @param string $tag the tag name
	 * @return string the generated HTML element tag
	 */
	public static function closeTag($tag)
	{
		return '</'.$tag.'>';
	}

	/**
	 * Generates a text field input for a model attribute.
	 * If the attribute has input error, the input field's CSS class will
	 * be appended with {@link errorCss}.
	 * @param CModel $model the data model
	 * @param string $attribute the attribute
	 * @param array $htmlOptions additional HTML attributes. Besides normal HTML attributes, a few special
	 * attributes are also recognized (see {@link clientChange} and {@link tag} for more details.)
	 * @return string the generated input field
	 * @see clientChange
	 * @see activeInputField
	 */
	public static function activeTextField($model,$attribute,$htmlOptions=array())
	{
		self::resolveNameID($model,$attribute,$htmlOptions);
		self::clientChange('change',$htmlOptions);
		return self::activeInputField('text',$model,$attribute,$htmlOptions);
	}

	/**
	 * Generates an input HTML tag for a model attribute.
	 * This method generates an input HTML tag based on the given data model and attribute.
	 * If the attribute has input error, the input field's CSS class will
	 * be appended with {@link errorCss}.
	 * This enables highlighting the incorrect input.
	 * @param string $type the input type (e.g. 'text', 'radio')
	 * @param CModel $model the data model
	 * @param string $attribute the attribute
	 * @param array $htmlOptions additional HTML attributes for the HTML tag
	 * @return string the generated input tag
	 */
	protected static function activeInputField($type,$model,$attribute,$htmlOptions)
	{
		$htmlOptions['type']=$type;
		if($type==='text' || $type==='password')
		{
			if(!isset($htmlOptions['maxlength']))
			{
				/*foreach($model->getValidators($attribute) as $validator)
				{
					if($validator instanceof \Fm\Validator\StringValidator && $validator->max!==null)
					{
						$htmlOptions['maxlength']=$validator->max;
						break;
					}
				}*/
			}
			elseif($htmlOptions['maxlength']===false) {
				unset($htmlOptions['maxlength']);
			}
		}

		if($type==='file')
			unset($htmlOptions['value']);
		elseif(!isset($htmlOptions['value']))
			$htmlOptions['value']=self::resolveValue($model,$attribute);
		if($model->hasErrors($attribute))
			self::addErrorCss($htmlOptions);
		return self::tag('input',$htmlOptions);
	}

	/**
	 * Generates a drop down list for a model attribute.
	 * If the attribute has input error, the input field's CSS class will
	 * be appended with {@link errorCss}.
	 * @param CModel $model the data model
	 * @param string $attribute the attribute
	 * @param array $data data for generating the list options (value=>display)
	 * You may use {@link listData} to generate this data.
	 * Please refer to {@link listOptions} on how this data is used to generate the list options.
	 * Note, the values and labels will be automatically HTML-encoded by this method.
	 * @param array $htmlOptions additional HTML attributes. Besides normal HTML attributes, a few special
	 * attributes are recognized. See {@link clientChange} and {@link tag} for more details.
	 * In addition, the following options are also supported:
	 * <ul>
	 * <li>encode: boolean, specifies whether to encode the values. Defaults to true.</li>
	 * <li>prompt: string, specifies the prompt text shown as the first list option. Its value is empty.  Note, the prompt text will NOT be HTML-encoded.</li>
	 * <li>empty: string, specifies the text corresponding to empty selection. Its value is empty.
	 * The 'empty' option can also be an array of value-label pairs.
	 * Each pair will be used to render a list option at the beginning. Note, the text label will NOT be HTML-encoded.</li>
	 * <li>options: array, specifies additional attributes for each OPTION tag.
	 *     The array keys must be the option values, and the array values are the extra
	 *     OPTION tag attributes in the name-value pairs. For example,
	 * <pre>
	 *     array(
	 *         'value1'=>array('disabled'=>true,'label'=>'value 1'),
	 *         'value2'=>array('label'=>'value 2'),
	 *     );
	 * </pre>
	 * </li>
	 * </ul>
	 * Since 1.1.13, a special option named 'unselectValue' is available. It can be used to set the value
	 * that will be returned when no option is selected in multiple mode. When set, a hidden field is
	 * rendered so that if no option is selected in multiple mode, we can still obtain the posted
	 * unselect value. If 'unselectValue' is not set or set to NULL, the hidden field will not be rendered.
	 * @return string the generated drop down list
	 * @see clientChange
	 * @see listData
	 */
	public static function activeDropDownList($model,$attribute,$data,$htmlOptions=array())
	{
		self::resolveNameID($model,$attribute,$htmlOptions);
		$selection=self::resolveValue($model,$attribute);
		$options="\n".self::listOptions($selection,$data,$htmlOptions);
		self::clientChange('change',$htmlOptions);

		if($model->hasErrors($attribute))
			self::addErrorCss($htmlOptions);

		$hidden='';
		if(!empty($htmlOptions['multiple']))
		{
			if(substr($htmlOptions['name'],-2)!=='[]')
				$htmlOptions['name'].='[]';

			if(isset($htmlOptions['unselectValue']))
			{
				$hiddenOptions=isset($htmlOptions['id']) ? array('id'=>self::ID_PREFIX.$htmlOptions['id']) : array('id'=>false);
				$hidden=self::hiddenField(substr($htmlOptions['name'],0,-2),$htmlOptions['unselectValue'],$hiddenOptions);
				unset($htmlOptions['unselectValue']);
			}
		}
		return $hidden . self::tag('select',$htmlOptions,$options);
	}

	/**
	 * Generates a hidden input.
	 * @param string $name the input name
	 * @param string $value the input value
	 * @param array $htmlOptions additional HTML attributes (see {@link tag}).
	 * @return string the generated input field
	 * @see inputField
	 */
	public static function hiddenField($name,$value='',$htmlOptions=array())
	{
		return self::inputField('hidden',$name,$value,$htmlOptions);
	}

	/**
	 * Generates an input HTML tag.
	 * This method generates an input HTML tag based on the given input name and value.
	 * @param string $type the input type (e.g. 'text', 'radio')
	 * @param string $name the input name
	 * @param string $value the input value
	 * @param array $htmlOptions additional HTML attributes for the HTML tag (see {@link tag}).
	 * @return string the generated input tag
	 */
	protected static function inputField($type,$name,$value,$htmlOptions)
	{
		$htmlOptions['type']=$type;
		$htmlOptions['value']=$value;
		$htmlOptions['name']=$name;
		if(!isset($htmlOptions['id']))
			$htmlOptions['id']=self::getIdByName($name);
		elseif($htmlOptions['id']===false)
			unset($htmlOptions['id']);
		return self::tag('input',$htmlOptions);
	}

	/**
	 * Appends {@link errorCss} to the 'class' attribute.
	 * @param array $htmlOptions HTML options to be modified
	 */
	protected static function addErrorCss(&$htmlOptions)
	{
		if(empty(self::$errorCss))
			return;

		if(isset($htmlOptions['class']))
			$htmlOptions['class'].=' '.self::$errorCss;
		else
			$htmlOptions['class']=self::$errorCss;
	}

	/**
	 * Generates the list options.
	 * @param mixed $selection the selected value(s). This can be either a string for single selection or an array for multiple selections.
	 * @param array $listData the option data (see {@link listData})
	 * @param array $htmlOptions additional HTML attributes. The following two special attributes are recognized:
	 * <ul>
	 * <li>encode: boolean, specifies whether to encode the values. Defaults to true.</li>
	 * <li>prompt: string, specifies the prompt text shown as the first list option. Its value is empty. Note, the prompt text will NOT be HTML-encoded.</li>
	 * <li>empty: string, specifies the text corresponding to empty selection. Its value is empty.
	 * The 'empty' option can also be an array of value-label pairs.
	 * Each pair will be used to render a list option at the beginning. Note, the text label will NOT be HTML-encoded.</li>
	 * <li>options: array, specifies additional attributes for each OPTION tag.
	 *     The array keys must be the option values, and the array values are the extra
	 *     OPTION tag attributes in the name-value pairs. For example,
	 * <pre>
	 *     array(
	 *         'value1'=>array('disabled'=>true,'label'=>'value 1'),
	 *         'value2'=>array('label'=>'value 2'),
	 *     );
	 * </pre>
	 * </li>
	 * <li>key: string, specifies the name of key attribute of the selection object(s).
	 * This is used when the selection is represented in terms of objects. In this case,
	 * the property named by the key option of the objects will be treated as the actual selection value.
	 * This option defaults to 'primaryKey', meaning using the 'primaryKey' property value of the objects in the selection.
	 * This option has been available since version 1.1.3.</li>
	 * </ul>
	 * @return string the generated list options
	 */
	public static function listOptions($selection,$listData,&$htmlOptions)
	{
		$raw=isset($htmlOptions['encode']) && !$htmlOptions['encode'];
		$content='';
		if(isset($htmlOptions['prompt']))
		{
			$content.='<option value="">'.strtr($htmlOptions['prompt'],array('<'=>'&lt;','>'=>'&gt;'))."</option>\n";
			unset($htmlOptions['prompt']);
		}
		if(isset($htmlOptions['empty']))
		{
			if(!is_array($htmlOptions['empty']))
				$htmlOptions['empty']=array(''=>$htmlOptions['empty']);
			foreach($htmlOptions['empty'] as $value=>$label)
				$content.='<option value="'.self::encode($value).'">'.strtr($label,array('<'=>'&lt;','>'=>'&gt;'))."</option>\n";
			unset($htmlOptions['empty']);
		}

		if(isset($htmlOptions['options']))
		{
			$options=$htmlOptions['options'];
			unset($htmlOptions['options']);
		}
		else
			$options=array();

		$key=isset($htmlOptions['key']) ? $htmlOptions['key'] : 'primaryKey';
		if(is_array($selection))
		{
			foreach($selection as $i=>$item)
			{
				if(is_object($item))
					$selection[$i]=$item->$key;
			}
		}
		elseif(is_object($selection))
			$selection=$selection->$key;

		foreach($listData as $key=>$value)
		{
			if(is_array($value))
			{
				$content.='<optgroup label="'.($raw?$key : self::encode($key))."\">\n";
				$dummy=array('options'=>$options);
				if(isset($htmlOptions['encode']))
					$dummy['encode']=$htmlOptions['encode'];
				$content.=self::listOptions($selection,$value,$dummy);
				$content.='</optgroup>'."\n";
			}
			else
			{
				$attributes=array('value'=>(string)$key,'encode'=>!$raw);
				if(!is_array($selection) && !strcmp($key,$selection) || is_array($selection) && in_array($key,$selection))
					$attributes['selected']='selected';
				if(isset($options[$key]))
					$attributes=array_merge($attributes,$options[$key]);
				$content.=self::tag('option',$attributes,$raw?(string)$value : self::encode((string)$value))."\n";
			}
		}

		unset($htmlOptions['key']);

		return $content;
	}

	/**
	 * Evaluates the attribute value of the model.
	 * This method can recognize the attribute name written in array format.
	 * For example, if the attribute name is 'name[a][b]', the value "$model->name['a']['b']" will be returned.
	 * @param CModel $model the data model
	 * @param string $attribute the attribute name
	 * @return mixed the attribute value
	 * @since 1.1.3
	 */
	public static function resolveValue($model,$attribute)
	{
		if(($pos=strpos($attribute,'['))!==false)
		{
			if($pos===0) // [a]name[b][c], should ignore [a]
			{
				if(preg_match('/\](\w+(\[.+)?)/',$attribute,$matches)) {
					$attribute=$matches[1]; // we get: name[b][c]
				}
				if(($pos=strpos($attribute,'['))===false) {
					return $model->$attribute;
				}
			}
			$name=substr($attribute,0,$pos);
			$value=$model->$name;
			foreach(explode('][',rtrim(substr($attribute,$pos+1),']')) as $id)
			{
				if((is_array($value) || $value instanceof \ArrayAccess) && isset($value[$id]))
					$value=$value[$id];
				else
					return null;
			}
			return $value;
		}
		else {
			return $model->$attribute;
		}
	}

	/**
	 * Generates input name and ID for a model attribute.
	 * This method will update the HTML options by setting appropriate 'name' and 'id' attributes.
	 * This method may also modify the attribute name if the name
	 * contains square brackets (mainly used in tabular input).
	 * @param CModel $model the data model
	 * @param string $attribute the attribute
	 * @param array $htmlOptions the HTML options
	 */
	public static function resolveNameID($model,&$attribute,&$htmlOptions)
	{
		if(!isset($htmlOptions['name']))
			$htmlOptions['name']=self::resolveName($model,$attribute);
		if(!isset($htmlOptions['id']))
			$htmlOptions['id']=self::getIdByName($htmlOptions['name']);
		elseif($htmlOptions['id']===false)
			unset($htmlOptions['id']);
	}

	/**
	 * Generates a hyperlink tag.
	 * @param string $text link body. It will NOT be HTML-encoded. Therefore you can pass in HTML code such as an image tag.
	 * @param mixed $url a URL or an action route that can be used to create a URL.
	 * See {@link normalizeUrl} for more details about how to specify this parameter.
	 * @param array $htmlOptions additional HTML attributes. Besides normal HTML attributes, a few special
	 * attributes are also recognized (see {@link clientChange} and {@link tag} for more details.)
	 * @return string the generated hyperlink
	 * @see normalizeUrl
	 * @see clientChange
	 */
	public static function link($text,$url='#',$htmlOptions=array())
	{
		if($url!=='') {
			$htmlOptions['href']=self::normalizeUrl($url);
		}
		self::clientChange('click',$htmlOptions);
		return self::tag('a',$htmlOptions,$text);
	}

	/**
	 * Normalizes the input parameter to be a valid URL.
	 *
	 * If the input parameter is an empty string, the currently requested URL will be returned.
	 *
	 * If the input parameter is a non-empty string, it is treated as a valid URL and will
	 * be returned without any change.
	 *
	 * If the input parameter is an array, it is treated as a controller route and a list of
	 * GET parameters, and the {@link CController::createUrl} method will be invoked to
	 * create a URL. In this case, the first array element refers to the controller route,
	 * and the rest key-value pairs refer to the additional GET parameters for the URL.
	 * For example, <code>array('post/list', 'page'=>3)</code> may be used to generate the URL
	 * <code>/index.php?r=post/list&page=3</code>.
	 *
	 * @param mixed $url the parameter to be used to generate a valid URL
	 * @return string the normalized URL
	 */
	public static function normalizeUrl($url)
	{
		if(is_array($url)) {
			if(isset($url[0])) {
				return \Core\Base\Action::getInstance()->url(array_splice($url,1), $url[0]);
			} else {
				$url = '';
			}
		}
		return $url==='' ? \Core\Http\Request::getInstance()->getBaseUrl() : $url;
	}

	/**
	 * Evaluates the value of the specified attribute for the given model.
	 * The attribute name can be given in a dot syntax. For example, if the attribute
	 * is "author.firstName", this method will return the value of "$model->author->firstName".
	 * A default value (passed as the last parameter) will be returned if the attribute does
	 * not exist or is broken in the middle (e.g. $model->author is null).
	 * The model can be either an object or an array. If the latter, the attribute is treated
	 * as a key of the array. For the example of "author.firstName", if would mean the array value
	 * "$model['author']['firstName']".
	 *
	 * Anonymous function could also be used for attribute calculation since 1.1.13
	 * ($attribute parameter; PHP 5.3+ only) as follows:
	 * <pre>
	 * $taskClosedSecondsAgo=CHtml::value($closedTask,function($model) {
	 * 	return time()-$model->closed_at;
	 * });
	 * </pre>
	 * Your anonymous function should receive one argument, which is the model, the current
	 * value is calculated from. This feature could be used together with the {@link listData}.
	 * Please refer to its documentation for more details.
	 *
	 * @param mixed $model the model. This can be either an object or an array.
	 * @param mixed $attribute the attribute name (use dot to concatenate multiple attributes)
	 * or anonymous function (PHP 5.3+). Remember that functions created by "create_function"
	 * are not supported by this method. Also note that numeric value is meaningless when
	 * first parameter is object typed.
	 * @param mixed $defaultValue the default value to return when the attribute does not exist.
	 * @return mixed the attribute value.
	 */
	public static function value($model,$attribute,$defaultValue=null)
	{
		if(is_scalar($attribute) || $attribute===null)
			foreach(explode('.',$attribute) as $name)
			{
				if(is_object($model) && isset($model->$name))
					$model=$model->$name;
				elseif(is_array($model) && isset($model[$name]))
					$model=$model[$name];
				else
					return $defaultValue;
			}
		else
			return call_user_func($attribute,$model);

		return $model;
	}

	protected static function clientChange($event,&$htmlOptions)
	{
		if(!isset($htmlOptions['submit']) && !isset($htmlOptions['confirm']) && !isset($htmlOptions['ajax']))
			return;
		if(isset($htmlOptions['live']))
		{
			$live=$htmlOptions['live'];
			unset($htmlOptions['live']);
		}
		else
			$live = self::$liveEvents;
		if(isset($htmlOptions['return']) && $htmlOptions['return'])
			$return='return true';
		else
			$return='return false';
		if(isset($htmlOptions['on'.$event]))
		{
			$handler=trim($htmlOptions['on'.$event],';').';';
			unset($htmlOptions['on'.$event]);
		}
		else
			$handler='';
		if(isset($htmlOptions['id']))
			$id=$htmlOptions['id'];
		else
			$id=$htmlOptions['id']=isset($htmlOptions['name'])?$htmlOptions['name']:self::ID_PREFIX.self::$count++;
		
		$cs = \Core\Base\Action::getComponent('document');
		$cs->addCoreScript('jquery');
		if(isset($htmlOptions['submit']))
		{
			$cs->addCoreScript('core');

			if(isset($htmlOptions['params']))
				$params=\Core\Javascript\Encode::encode($htmlOptions['params']);
			else
				$params='{}';
			if($htmlOptions['submit']!=='')
				$url=\Core\Javascript\Encode::quote(self::normalizeUrl($htmlOptions['submit']));
			else
				$url='';
			$handler.="jQuery.core.submitForm(this,'$url',$params);{$return};";
		}
		if(isset($htmlOptions['ajax']))
			$handler.=self::ajax($htmlOptions['ajax'])."{$return};";
		if(isset($htmlOptions['confirm']))
		{
			$confirm='confirm(\''.\Core\Javascript\Encode::quote($htmlOptions['confirm']).'\')';
			if($handler!=='')
				$handler="if($confirm) {".$handler."} else return false;";
			else
				$handler="return $confirm;";
		}
		if($live)
			$cs->$value('Core.Html.#' . $id,"jQuery('body').on('$event','#$id',function(){{$handler}});");
		else
			$cs->$value('Core.Html.#' . $id,"jQuery('#$id').on('$event', function(){{$handler}});");
		unset($htmlOptions['params'],$htmlOptions['submit'],$htmlOptions['ajax'],$htmlOptions['confirm'],$htmlOptions['return'],$htmlOptions['csrf']);
	}

	
	
}