<?php

declare(strict_types=1);

namespace System\Exceptions\Instance;
use System\Exceptions\Instance\Instance;


class SQLException extends Instance
{
	protected $code = 1;
	protected $message = "Database exception";
}