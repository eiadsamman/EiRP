<?php

declare(strict_types=1);

namespace System\Finance\Transaction;


enum Nature: int 
{
	case Income = 1;
	case Payment = 2;
	case Transfer = 3;
	case Exchange = 4;
	case Balance = 5;


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
			self::Income => 'Income',
			self::Payment => 'Payment',
			self::Transfer => 'Transfer',
			self::Exchange => 'Exchange',
			self::Balance => 'Balance'
		};
	}
}