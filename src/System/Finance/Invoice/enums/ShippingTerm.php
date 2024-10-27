<?php
declare(strict_types=1);

namespace System\Finance\Invoice\enums;

enum ShippingTerm: int
{
	use \System\enumLib;
	case NA = 0;

	case EXW = 100;
	case FCA = 110;
	case FAS = 120;
	case FOB = 130;
	case CFR = 140;
	case CIF = 150;
	case CPT = 160;
	case CIP = 170;
	case DAT = 180;
	case DAP = 190;
	case DDP = 200;


	public function toString(): string
	{
		return match ($this) {
			self::EXW => "Ex Works",
			self::FCA => "Free Carrier",
			self::FAS => "Free Alongside Ship",
			self::FOB => "Free on Board",
			self::CFR => "Cost and Freight",
			self::CIF => "Cost, Insurance and Freight",
			self::CPT => "Cost Paid to..",
			self::CIP => "Carrier and Insurance Paid to..",
			self::DAT => "Delivery at Terminal",
			self::DAP => "Delivery at Place",
			self::DDP => "Delivery Duty Paid"
		};
	}
}
