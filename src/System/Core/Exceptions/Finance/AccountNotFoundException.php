<?php

declare(strict_types=1);

namespace System\Core\Exceptions\Finance;

use System\Core\Exceptions\Finance\Finance;



class AccountNotFoundException extends Finance
{
	protected $message = "Account not found";
}