<?php
declare(strict_types=1);

namespace System\Timeline;

enum Action: int
{
	use \System\enumLib;
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

	public function toString(): string
	{
		return match ($this) {
			self::Create => 'New entry',
			self::Modify => 'Entry modified',
			self::Delete => 'Entry deleted',
			self::ApprovalPending => 'Pending approval',
			self::ApprovalApproved => 'Approved',
			self::Mention => 'Mentiond',
			self::Feedback => 'Comments',
			self::FinanceReceipt => 'Cash receipt',
			self::FinancePayment => 'Cash payment',
			self::PhoneCall => 'Phone call',
			self::Email => 'E-mail message',
			self::Empty => '',
		};
	}
}

