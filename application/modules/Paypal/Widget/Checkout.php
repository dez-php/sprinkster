<?php
namespace Paypal\Widget;

use \Core\Http\Url;

class Checkout extends \Core\Base\Widget {

	public function init() {
		$this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function result()
	{
		$seller = isset($this->options['seller']) ? $this->options['seller'] : NULL;
		$order = isset($this->options['order']) ? $this->options['order'] : NULL;
		$upgrade = isset($this->options['upgrade']) ? $this->options['upgrade'] : NULL;

		if(!is_object($order))
			return;

		$this->render('checkout', [ 'seller' => $seller, 'order' => $order, 'upgrade' => $upgrade ]);
	}

}