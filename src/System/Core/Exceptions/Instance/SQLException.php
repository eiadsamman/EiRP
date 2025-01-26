<?php

declare(strict_types=1);

namespace System\Core\Exceptions\Instance;
use System\Core\Exceptions\Instance\Instance;


class SQLException extends Instance
{
	protected $code = 1;
	protected $message = "Database exception";
}