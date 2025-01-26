<?php

declare(strict_types=1);

namespace System\Controller\Individual\Attendance;


use Exception;


class ExceptionDuplicateCheckin extends Exception
{
	public function errorPlot()
	{
		return $this->getMessage();
	}
}
