<?php

declare(strict_types=1);

namespace System\Finance;


class Account
{
	public int $id;
	public string $name;
	public $currency;
	public \System\Finance\AccountRole $role;
}