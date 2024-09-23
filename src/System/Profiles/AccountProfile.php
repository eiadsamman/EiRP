<?php
declare(strict_types=1);

namespace System\Profiles;

use System\Finance\Currency;
use System\Finance\AccountRole;

use System\Finance\Term\Asset;
use System\Finance\Term\Equity;
use System\Finance\Term\IncomeStatement;
use System\Finance\Term\Liability;

class AccountProfile
{
	protected int $internalId;
	public int $id;
	public string $name;
	public ?float $balance;

	public Asset|Liability|Equity|IncomeStatement|null $term;

	public Currency $currency;
	public AccountRole $role;
	public CompanyProfile $company;

}

