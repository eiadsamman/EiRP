<?php

declare(strict_types=1);

namespace System\Controller\Individual\Attendance;


use Exception;


class ExceptionCheckedout extends Exception
{
	public function errorPlot()
	{
		return $this->getMessage();
	}
}
