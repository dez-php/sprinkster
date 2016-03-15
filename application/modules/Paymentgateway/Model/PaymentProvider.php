<?php

namespace Paymentgateway;

class PaymentProvider extends \Base\Model\Reference {

	public function activate($code)
	{
		$this->modify($code, 'active', TRUE);
	}

	public function deactivate($code)
	{
		$this->modify($code, 'active', FALSE);
	}

	public function priority($code, $priority)
	{
		$this->modify($code, 'priority', $priority ? (int) $priority : NULL);
	}

	private function modify($code, $field, $value)
	{
		$record = (new self)->fetchRow([ 'code = ?' => $code ]);
		$record->$field = $value;
		$record->save();
	}

}