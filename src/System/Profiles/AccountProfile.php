<?php
declare(strict_types=1);

namespace System\Profiles;

use System\Finance\Type;
use System\Finance\Currency;
use System\Finance\AccountRole;

class AccountProfile
{
	protected int $internalId;
	public int $id;
	public string $name;
	public ?float $balance;

	public Type $type;
	public Currency $currency;
	public AccountRole $role;
	public CompanyProfile $company;

}

