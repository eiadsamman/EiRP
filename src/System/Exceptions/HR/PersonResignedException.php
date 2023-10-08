<?php

declare(strict_types=1);

namespace System\Exceptions\HR;
use System\Exceptions\HR\HR;
class PersonResignedException extends HR
{
	public function errorPlot()
	{
		return $this->getMessage();
	}
}
