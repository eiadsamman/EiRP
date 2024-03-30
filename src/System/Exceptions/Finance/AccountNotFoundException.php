<?php

declare(strict_types=1);

namespace System\Exceptions\Finance;

use System\Exceptions\Finance\Finance;



class AccountNotFoundException extends Finance
{
	protected $message = "Account not found";
}