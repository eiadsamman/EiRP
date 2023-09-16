<?php

declare(strict_types=1);

namespace System\Exceptions\HR;
use System\Exceptions\HR\HR;


class CompanyRegisteringException extends HR
{
	protected $code = 1;
	protected $message = "Company regisering failed";

}