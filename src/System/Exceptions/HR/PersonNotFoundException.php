<?php

declare(strict_types=1);

namespace System\Exceptions\HR;
use System\Exceptions\HR\HR;

class PersonNotFoundException extends HR
{
	public function errorPlot()
	{
		return $this->getMessage();
	}
}
