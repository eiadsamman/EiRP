<?php

declare(strict_types=1);

namespace System\Core\Exceptions\HR;
use System\Core\Exceptions\HR\HR;


class InvalidLoginException extends HR
{
	protected $code = 3;
	protected $message = "Invalid Login";
}