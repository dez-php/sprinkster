<?php

namespace Interest\Widget;

class Balloons extends \Base\Widget\PermissionWidget {

	const DefaultOrder = 'followers DESC';
	const DefaultLimit = null;

	public function init() {
		$this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function option($key)
	{
		return isset($this->options[$key]) ? $this->options[$key] : NULL;
	}

	public function result()
	{
		$filter = $this->option('filter') ?: [];
		$limit = (int) $this->option('limit') ?: self::DefaultLimit;

		if(0 >= $limit)
			$limit = self::DefaultLimit;

		$criteria = [];

		if(isset($filter['show']) && $filter['show'])
			$criteria[] = '`show` = ' . (int) $filter['show'];

		if(isset($filter['category_id']) && $filter['category_id'])
			$criteria['category_id = ?'] = (int) $filter['category_id'];

		$order = $this->option('order') ?: self::DefaultOrder;

		$interests = (new \Interest\Interest)->fetchAll($criteria, $order, $limit);

		$this->render('balloons', [ 'interests' => $interests, 'empty_text' => $this->option('empty_text') ] );
	}

}