<?php

declare(strict_types=1);

namespace System\Core\Exceptions\HR;
use System\Core\Exceptions\HR\HR;


class CompanyRegisteringException extends HR
{
	protected $code = 1;
	protected $message = "Company regisering failed";

}