<?php
declare(strict_types=1);

namespace System\Controller\Finance\Invoice\enums;

enum Sale: int
{
	use \System\Core\EnumLib;
	case NA = 0;
	case Quotation = 210;
	case Order = 220;
	case Acknowledgment = 230;
	case GoodsDelivery = 240;
	case Invoice = 250;

	public function toString(): string
	{
		return match ($this) {
			self::Quotation => 'Material Request',

		};
	}
}