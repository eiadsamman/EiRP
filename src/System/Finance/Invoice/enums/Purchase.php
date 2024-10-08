<?php
declare(strict_types=1);

namespace System\Finance\Invoice\enums;

enum Purchase: int
{
	use \System\enumLib;
	case NA = 0;
	case Request = 100;
	case Quotation = 110;
	case Order = 120;
	case GoodsReceivedNote = 130;
	case GoodsReceivedInspection = 140;
	case Invoice = 150;

	public function toString(): string
	{
		return match ($this) {
			self::Request => 'Material Request',

		};
	}
}
