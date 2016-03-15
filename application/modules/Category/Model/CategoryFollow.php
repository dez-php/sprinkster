<?php

namespace Category;

class CategoryFollow extends \Base\Model\Reference {
	
	public function statistic($category_id) {
		return [
			'id' => $category_id,
			'group' => 'category',
			'stats' => [
				'followers' => $this->countBy(['category_id' => $category_id])
			]
		];
	}
	
}