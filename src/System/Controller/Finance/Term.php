<?php

declare(strict_types=1);

namespace System\Controller\Finance;
use System\Controller\Finance\Term\Asset;
use System\Controller\Finance\Term\Liability;
use System\Controller\Finance\Term\Equity;
use System\Controller\Finance\Term\IncomeStatement;

class Term
{
	private static array $currentAccounts = [
		Asset::Cash,
		Asset::Inventory,
		Asset::Checks,
		Asset::AccountsReceivable,
		Asset::PrepaidExpenses,
		Asset::AssestHeldForSale,

		Liability::AccountsPayable,
		Liability::TaxesPayable,
		Liability::AccuredExpenses,
		Liability::DividendsPayable,
		Liability::DeferredRevenue,
		Liability::WagesPayable,
		Liability::IncomeTaxesPayable,
	];
	private static array $revenueAccounts = [
		IncomeStatement::SalesRevenue,
		IncomeStatement::ServiceRevenue,
		IncomeStatement::InterestRevenue,
		IncomeStatement::OtherRevenue
	];




	public static function isCurrent(Asset|Liability|Equity|IncomeStatement $type): bool
	{
		return in_array($type, self::$currentAccounts);
	}

	public static function isRevenue(Asset|Liability|Equity|IncomeStatement $type): bool
	{
		return in_array($type, self::$revenueAccounts);
	}

	public static function val(Asset|Liability|Equity|IncomeStatement $type): int
	{
		return $type->value;
	}


	public static function from(int $id): Asset|Liability|Equity|IncomeStatement|null
	{
		$o = null;
		if ($id >= 10000 && $id < 20000)
			$o = Asset::tryFrom($id);

		if ($id >= 20000 && $id < 30000)
			$o = Liability::tryFrom($id);

		if ($id >= 30000 && $id < 40000)
			$o = Equity::tryFrom($id);

		if ($id >= 40000 && $id < 50000)
			$o = IncomeStatement::tryFrom($id);

		return $o;
	}

}
