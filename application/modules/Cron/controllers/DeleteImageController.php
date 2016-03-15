<?php

namespace Cron;

class DeleteImageController extends \Core\Base\Action {

	public function init() {
		$this->noLayout(true);
		set_time_limit(0);
		ignore_user_abort(true);
	}
	
	public function indexAction() {
		
		$imagesTable = new \Base\ImageDelete();

		$images = $imagesTable->fetchAll( null, null, 50 );
		foreach($images AS $image) {
			\Core\Http\Thread::run(array('\\'.$image->store . '\Helper\Upload','delete', strtolower($image->group)), $image->image);
			$image->delete();
		}
	}
}