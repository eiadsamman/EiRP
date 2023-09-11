<?php

declare(strict_types=1);

namespace System\Individual\Attendance;


use Exception;


class LocationInvalid extends Exception
{
	public function errorPlot()
	{
		return $this->getMessage();
	}
}
