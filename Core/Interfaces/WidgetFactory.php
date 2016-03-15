<?php

namespace Core\Interfaces;

interface WidgetFactory {
	public function createWidget($owner,$className,$properties=array());
}