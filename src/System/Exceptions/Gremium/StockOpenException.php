<?php

declare(strict_types=1);

namespace System\Exceptions\Gremium;
use System\Exceptions\Gremium\Gremium;


class StockOpenException extends Gremium
{
	protected $code = 1;
	protected $message = "Stack is opened and must be closed before opening a new block";

}