<?php

declare(strict_types=1);

namespace System\Core\Exceptions\Finance;

use System\Core\Exceptions\Finance\Finance;



class TransactionException extends Finance
{
	protected $message = "Transaction posting failed";
}