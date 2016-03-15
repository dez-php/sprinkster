<?php

namespace Pin\Widget\Grid;

abstract class Gridorder {
	
	protected $order;
	
	protected $options;
	
	abstract public function __construct($order, $options = array());
	
	abstract public function getOrder();
	
	abstract public function getLabels();
	
	abstract public function getBadges();
	
}