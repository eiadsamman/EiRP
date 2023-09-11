<?php

declare(strict_types=1);

namespace System\Individual;

class PersonNotFoundException extends \Exception
{
	public function errorPlot()
	{
		return $this->getMessage();
	}
}
