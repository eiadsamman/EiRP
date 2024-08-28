<?php
declare(strict_types=1);

namespace System\Timeline;

enum Action: int
{
	case Create = 110;
	case Modify = 120;
	case Delete = 130;
	
	case ApprovalPending = 140;
	case ApprovalApproved = 150;

	
	case FinanceReceipt = 210;
	case FinancePayment = 220;
	case FinanceModify = 230;

	
	case Mention = 510;
	case PhoneCall = 520;
	case Feedback = 530;
	case Email = 540;

	case Empty = 0;


	case InventorySend = 310;
	case InventoryReceive = 320;


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
			self::Create => 'إضافة قيد جديد',
			self::Modify => 'تعديل قيد',
			self::Delete => 'حذف قيد',
			self::ApprovalPending => 'بانتظار الموافقة',
			self::ApprovalApproved => 'تمت الموافقة',
			self::Mention => 'Mentiond',
			self::Feedback => 'تعليقات وملاحظات',
			self::FinanceReceipt => 'استلام نقدي',
			self::FinancePayment => 'دفع نقدي',
			self::PhoneCall => 'اتصال هاتفي',
			self::Email => 'مراسلة بريد الكتروني',
			self::Empty => '',
		};
	}
}

