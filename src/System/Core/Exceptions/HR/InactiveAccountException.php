<?php

declare(strict_types=1);

namespace System\Core\Exceptions\HR;
use System\Core\Exceptions\HR\HR;


class InactiveAccountException extends HR
{
	protected $code = 2;
	protected $message = "Invalid Login";
}