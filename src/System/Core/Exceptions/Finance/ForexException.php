<?php

declare(strict_types=1);

namespace System\Core\Exceptions\Finance;

use System\Core\Exceptions\Finance\Finance;


class ForexException extends Finance
{
	protected $message = "Forex exception failed";
}