<?php
namespace Paymentgateway;

abstract class AbstractWithdrawHandler extends AbstractHandler {

	public abstract function withdraw($user_id, $amount);

}