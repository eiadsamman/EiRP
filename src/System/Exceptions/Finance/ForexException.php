<?php

declare(strict_types=1);

namespace System\Exceptions\Finance;

use System\Exceptions\Finance\Finance;


class ForexException extends Finance
{
	protected $message = "Forex exception failed";
}