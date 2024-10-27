<?php
declare(strict_types=1);

namespace System\Finance\Invoice\enums;

enum PaymentTerm: int
{
	use \System\enumLib;
	case NA = 0;

	case Net07 = 100;
	case Net15 = 101;
	case Net30 = 102;
	case Net60 = 103;
	case Net90 = 104;
	case Net120 = 105;

	case D2_10_Net30 = 130;
	case D2_10_Net45 = 131;
	case D3_10_Net30 = 132;
	case D3_20_Net60 = 133;
	case D2_EOM_Net45 = 143;

	case Upfront10 = 160;
	case Upfront25 = 161;
	case Upfront30 = 162;
	case Upfront50 = 163;
	case Upfront70 = 164;


	case CashInAdvance = 210;
	case CashWithOrder = 220;
	case EndOfMonth = 230;
	case CashOnDelivery = 240;
	case CashBeforeShipment = 250;
	case DueUponReceipt = 260;
	case LineOfCredit = 270;
	case OpenAccount = 280;
	case ContraPayment = 290;



	public function getDueDate(\DateTime $dateTime): \DateTime|bool
	{
		$offsets = [
			self::Net07->value => 7,
			self::Net15->value => 15,
			self::Net30->value => 30,
			self::Net60->value => 60,
			self::Net120->value => 120,
			self::D2_10_Net30->value => 30,
			self::D2_10_Net45->value => 45,
			self::D3_10_Net30->value => 30,
			self::D3_20_Net60->value => 60,
			self::D2_EOM_Net45->value => 45
		];

		if (array_key_exists($this->value, $offsets)) {
			/* Offset days */
			$termOffset   = $offsets[$this->value];
			$dateInterval = new \DateInterval("P{$termOffset}D");
			return $dateTime->add($dateInterval);
		}

		if (in_array($this->value, [self::EndOfMonth->value])) {
			/* End of Month */
			return $dateTime->modify("last day of this month");
		}
		return false;
	}
	public function toString(): string
	{
		return match ($this) {
			self::Net07 => "Net 7",
			self::Net15 => "Net 15",
			self::Net30 => "Net 30",
			self::Net60 => "Net 60",
			self::Net90 => "Net 90",
			self::Net120 => "Net 120",

			self::D2_10_Net30 => "2/10 net 30",
			self::D2_10_Net45 => "2/10 net 45",
			self::D3_10_Net30 => "3/10 net 30",
			self::D3_20_Net60 => "2/20 net 60",
			self::D2_EOM_Net45 => "2/EOM net 45",



			self::Upfront10 => "10 Upfront",
			self::Upfront25 => "25 Upfront",
			self::Upfront30 => "30 Upfront",
			self::Upfront50 => "50 Upfront",
			self::Upfront70 => "70 Upfront",

			self::CashInAdvance => "Cash in Advanced",
			self::CashWithOrder => "Cash with Order",
			self::EndOfMonth => "End of Month",
			self::CashOnDelivery => "Cash on Delivery",
			self::CashBeforeShipment => "Cash before Shipment",
			self::DueUponReceipt => "Due upon Receipt",
			self::LineOfCredit => "Line of Credit",
			self::OpenAccount => "Open Account",
			self::ContraPayment => "Contra Payment",
		};
	}
}
