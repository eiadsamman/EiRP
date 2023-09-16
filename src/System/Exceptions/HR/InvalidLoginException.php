<?php

declare(strict_types=1);

namespace System\Exceptions\HR;
use System\Exceptions\HR\HR;


class InvalidLoginException extends HR
{
	protected $code = 3;
	protected $message = "Invalid Login";
}