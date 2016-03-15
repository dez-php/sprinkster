<?php

namespace Pin\Widget;

class RecentlyFeatured extends Grid {
	
	protected $view = 'recentlyfeatured';

	public function init() {
		$this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function count() {
		extract($this->filter_data);

		$filter = isset($filter) && isset($filter) ? $filter : '';
		$order = isset($order) ? $order : 'id DESC';
		$limit = isset($limit) ? (int) $limit : 25;
		$offset = isset($offset) ? (int) $offset : NULL;
		$use_index = isset($use_index) ? !!$use_index : NULL;
		$repins = isset($repins) ? !!$repins : FALSE;

		return (new \Pin\Pin)->getAll($filter, $order, 0, $offset, $use_index, $repins)->count();
		//return (new \Pin\Pin())->countBy($this->filterBuilder()[0]);
		
	}

	public function result()
	{
		$data = array();
		$request = $this->getRequest();
		if (!isset($this->options['module'])) {
			if ($request->getRequest('category_id')) {
				$this->options['module'] = $request->getRequest('category_id');
			} else {
				$this->options['module'] = $request->getModule();
			}
		}
		$data['pins'] = $this->getPins();
		$this->options['fromRow'] = $this->offset;
		$this->options['toRow'] = $this->offset + $this->step_limit;

		$data['limit'] = $this->step_limit;
		//$data['query'] = 'options='.base64_encode(serialize($this->options));
		$this->options['filter'] = $this->filter;
		// 		$data['query'] = http_build_query(array('options' => $this->options));
		$data['query'] = 'options=' . urlencode(serialize($this->options));
		$data['from_widget'] = $request->getRequest('widget');

		$data['labels'] = $this->labels;
		$data['badges'] = $this->badges;

		$this->render($this->view, $data);
	}
	
}