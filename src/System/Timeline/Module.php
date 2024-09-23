<?php
declare(strict_types=1);

namespace System\Timeline;

enum Module: int
{
	use \System\enumLib;
	case Company = 1010;
	case CompanyLegal = 1020;
	case CompanyBank = 1030;
	case Account = 1110;
	case AccountBank = 1120;
	case FinanceCash = 1210;
	case HR = 1310;
	case CRMCustomer = 1410;
	case Inventory = 1510;

	public function toString(): string
	{
		return match ($this) {
			self::Company => 'Company',
			self::CompanyBank => 'Company Bank Account',
			self::CompanyLegal => 'Company Registration',
			self::Account => 'Account',
			self::AccountBank => 'Account Linked Bank',
			self::FinanceCash => 'Finanace - Cash',
			self::HR => 'HR',
			self::CRMCustomer  => 'CRM Customers',
			self::Inventory => 'Inventory',

		};
	}
}

