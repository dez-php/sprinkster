<?php

namespace Widget\Grid;

class Autocomplete extends \Core\Base\Widget {

	public function result() {
		$request = $this->getRequest();
		$table = '\\' . ltrim(urldecode($request->getQuery('table')),'\\');
		$query = $request->getQuery('query');
		
		$json = [];
		
		try {
			$table = new $table();
			if(method_exists($table, 'autocomplete')) {
				$json = $table->autocomplete($query);
			}
		} catch (\Exception $e) {}
		
		$this->responseJsonCallback($json);
		
	}
	
}