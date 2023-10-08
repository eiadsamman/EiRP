<?php

declare(strict_types=1);

namespace System\Finance;


enum KeyTerm: int
{
	case Assets = 1;
	case Liabilities = 2;
	case Equity = 3;


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
			self::Assets => 'Assets',
			self::Liabilities => 'Liabilities',
			self::Equity => 'Equity',
		};
	}
}