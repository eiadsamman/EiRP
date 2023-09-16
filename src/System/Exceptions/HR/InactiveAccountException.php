<?php

declare(strict_types=1);

namespace System\Exceptions\HR;
use System\Exceptions\HR\HR;


class InactiveAccountException extends HR
{
	protected $code = 2;
	protected $message = "Invalid Login";
}