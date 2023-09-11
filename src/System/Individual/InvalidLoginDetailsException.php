<?php

declare(strict_types=1);

namespace System\Individual;


class InvalidLoginDetailsException extends \Exception
{
	public function errorPlot()
	{
		return $this->getMessage();
	}
}
