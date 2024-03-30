<?php

declare(strict_types=1);

namespace System\Exceptions\Finance;

use System\Exceptions\Finance\Finance;



class TransactionException extends Finance
{
	protected $message = "Transaction posting failed";
}