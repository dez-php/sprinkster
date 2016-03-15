<?php

namespace Core\Interfaces;

interface DataProvider {
	public function getId();
	public function getItemCount($refresh=false);
	public function getTotalItemCount($refresh=false);
	public function getData($refresh=false);
	public function getKeys($refresh=false);
	public function getSort();
	public function getPagination();
}