<?php

declare(strict_types=1);

namespace System\Core\Exceptions\HR;
use System\Core\Exceptions\HR\HR;
class PersonResignedException extends HR
{
	public function errorPlot()
	{
		return $this->getMessage();
	}
}
