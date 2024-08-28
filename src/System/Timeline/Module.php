<?php
declare(strict_types=1);

namespace System\Timeline;

enum Module: int
{
	case Company = 1010;
	case CompanyLegal = 1020;
	case CompanyBank = 1030;


	case Account = 1110;
	case AccountBank = 1120;


	case FinanceCash = 1210;
	
	case HR = 1310;

	case CRMCustomer = 1410;

	case Inventory = 1510;



	public static function names(): array
	{
		return array_column(self::cases(), 'name');
	}

	public static function values(): array
	{
		return array_column(self::cases(), 'value');
	}

	public static function array(): array
	{
		return array_combine(self::values(), self::names());
	}
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

