<?php
declare(strict_types=1);

namespace System\Profiles;

use System\Controller\Finance\Currency;
use System\Controller\Finance\AccountRole;

use System\Controller\Finance\Term\Asset;
use System\Controller\Finance\Term\Equity;
use System\Controller\Finance\Term\IncomeStatement;
use System\Controller\Finance\Term\Liability;

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

