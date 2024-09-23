<?php

declare(strict_types=1);

namespace System\Finance\Invoice;


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

enum Sales: int
{
	use \System\enumLib;
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






class Invoice
{
	public function __construct(
		protected \System\App &$app
	) {


	}



}
