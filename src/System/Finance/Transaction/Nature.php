<?php

declare(strict_types=1);

namespace System\Finance\Transaction;


enum Nature: int
{
	use \System\enumLib;
	case Receipt = 1;
	case Payment = 2;
	case Transfer = 3;
	case Exchange = 4;
	case Balance = 5;


	public function toString(): string
	{
		return match ($this) {
			self::Receipt => 'Receipt',
			self::Payment => 'Payment',
			self::Transfer => 'Transfer',
			self::Exchange => 'Exchange',
			self::Balance => 'Balance'
		};
	}
}