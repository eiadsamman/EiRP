<?php

declare(strict_types=1);

namespace System\Controller\Finance\Term;

enum Liability: int
{
	use \System\Core\EnumLib;

	case AccountsPayable = 20000;
	case TaxesPayable = 20010;
	case AccuredExpenses = 20020;
	case DividendsPayable = 20030;
	case DeferredRevenue = 20040;
	case WagesPayable = 20050;
	case IncomeTaxesPayable = 20060;


	case LongTermLoans = 25050;

	public function termType(): string
	{
		return "Liability";
	}
}