<?php

declare(strict_types=1);

namespace System\Exceptions;

class Exceptions extends \Exception
{
	protected int $major_code = 100;

	public function __construct(string|null $message = "", int|null $code = 0, \Throwable|null $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}

}