<?php

namespace Base\Model;

use \JsonSerializable;

use \Core\Text\String;

class SearchResult extends \Core\Base\Core implements JsonSerializable {

	protected $module = NULL;
	protected $target = NULL;
	protected $ID = 0;
	protected $title = NULL;
	protected $description = NULL;
	protected $url = NULL;
	protected $thumb = NULL;
	protected $icon = NULL;
	protected $label = NULL;
	protected $segment = NULL;

	protected $popup = FALSE;

	const UID_SEPARATOR = ':';

	public function __construct($module, $id, $title, $description, $url, $thumb, $icon, $label, $popup = FALSE, $target = NULL)
	{
		$this->module = $module;
		$this->target = $target ?: $module;

		$this->ID = $id;
		$this->title = $title;
		$this->description = $description;
		$this->url = $url;
		$this->thumb = $thumb;
		$this->icon = $icon;
		$this->label = $label;
		$this->popup = !!$popup;
	}

	public function __get($name)
	{
		return property_exists($this, $name) ? $this->name : NULL;
	}

	public function UID()
	{
		return $this->target . self::UID_SEPARATOR . $this->ID;
	}

	public function is_valid()
	{
		return $this->target && $this->ID && $this->url;
	}

	public function jsonSerialize()
	{
		return [
			'id' => $this->ID,
			'title' => $this->title,
			'description' => String::cut(String::plainify($this->description), 35),
			'url' => $this->url,
			'thumb' => $this->thumb,
			'icon' => $this->icon,
			'label' => $this->label,
			'popup' => !!$this->popup
		];
	}

	public function json()
	{
		return \Core\Encoders\Json::encode($this);
	}

}