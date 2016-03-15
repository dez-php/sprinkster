<?php

namespace Paymentgateway;

interface IPurchasable
{
	public function getUnitValue();
	public function getValue();
}