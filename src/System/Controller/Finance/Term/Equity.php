<?php

declare(strict_types=1);

namespace System\Controller\Finance\Term;

enum Equity: int
{
	use \System\Core\EnumLib;
	case CommenStock = 30000;
	case AdditionPaidInCapital = 30010;
	case RetainedEarnings = 30020;

	public function termType(): string
	{
		return "Equity";
	}
}
