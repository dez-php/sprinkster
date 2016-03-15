<?php

namespace User\Widget;

class Pinprofilerepin extends \Core\Base\Widget {

	protected $pin;
	protected $limit;

	public function init() {
		$this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function setLimit($limit) {
		$this->limit = $limit;
		return $this;
	}

	public function setPin($pin) {
		$this->pin = $pin;
		return $this;
	}

	public function getPage() {
		$request = $this->getRequest();
		$limit = (int) $request->getQuery('limit');
		if ($limit < 1) {
			$limit = 1;
		}

		$page = (int) $request->getQuery('page');
		if ($page < 1) {
			$page = 1;
		}

		$data['popup'] = $request->getQuery('popup');
		$offset = ($page * $limit) - $limit;

		$pin_id = $request->getQuery('pin_id');
		$pinTable = new \Pin\Pin();
		$pin_info = $pinTable->fetchRow(array('id = ?' => $pin_id));
		if ($pin_info) {
			$data['pin'] = $pin_info;
			$userTable = new \User\User();

			$data['users'] = \User\User::toRowset(\Core\Db\Init::getDefaultAdapter()->fetchAll('
						SELECT u.*
						FROM pin_repin r
						LEFT JOIN user u
						ON (u.id = r.user_id)
						WHERE r.pin_id = ?
						ORDER BY r.date_added DESC
						LIMIT ?,?
					', [ (int) $pin_id, (int) $offset, (int) $limit ]
			));


			$this->responseJsonCallback($this->render('page', $data, true));
		} else {
			$this->responseJsonCallback(false);
		}
	}

	public function result() {

		$request = $this->getRequest();

		if ($request->getQuery('pin_id') && $request->getQuery('page')) {
			return $this->getPage();
		}
		if(!$this->pin)
			return;

		$data = array(
			'users' => false,
			'init' => false
		);
		$data['popup'] = $request->getQuery('popup');

// 		if ($request->getRequest('getuserinfo') == 'true') {
			$pin_info = $this->pin;

			$pinRepinTable = new \Pin\PinRepin();
			$data['total'] = $pinRepinTable->countBy(['pin_id' => $this->pin->id]);

			if ($pin_info) {

				if ((int) $this->limit) {
					$limit = min(9, (int) $this->limit);
				} else {
					$limit = min(9, $data['total']);
				}

				if ($limit < 1) {
					return;
				}

				$data['limit'] = $limit;
				$this->limit = min(9, (int) $this->limit ? (int) $this->limit : 9);

				$data['users'] = \User\User::toRowset(\Core\Db\Init::getDefaultAdapter()->fetchAll('
						SELECT u.*
						FROM pin_repin r
						LEFT JOIN user u
						ON (u.id = r.user_id)
						WHERE r.pin_id = ?
						ORDER BY r.date_added DESC
						LIMIT ?
					', [ (int) $request->getQuery('pin_id'), (int) $limit]
				));

			}
// 		} else {
// 			$data['init'] = true;
// 			$data['pin_id'] = $this->pin->id;
// 		}

		$this->render('pinprofilelike', $data);
	}

}
