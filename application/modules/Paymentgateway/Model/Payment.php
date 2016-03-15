<?php

namespace Paymentgateway;

class Payment {

	const PURCHASE_SUPPORT             = 1;
	const SUBSCRIPTION_SUPPORT         = 2;
	const CHAIN_SUPPORT                = 4;
	const DEPOSIT_SUPPORT              = 8;
	const WITHDRAW_SUPPORT             = 16;

	const PURCHASE                     = 300;
	const SUBSCRIPTION                 = 301;
	const CHAIN                        = 302;
	const DEPOSIT                      = 303;
	const WITHDRAW                     = 304;

	const STATUS_FAILURE               = -3;
	const STATUS_INVALID_RESPONSE      = -2;
	const STATUS_NO_CREDENTIALS        = -1;
	const STATUS_UNKNOWN               = 0;
	const STATUS_OK                    = 1;
	
}