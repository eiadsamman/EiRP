<?php
declare(strict_types=1);

namespace System\Controller\Timeline;

class Describer
{

	static public function describe(TimelineEntry $entry): string
	{
		return match ($entry->action) {
			Action::FinanceReceipt => self::FinanceReceipt($entry),
			Action::FinancePayment => self::FinancePayment($entry),
			Action::Create => "",
			Action::Delete => "",
			Action::PhoneCall => "",
			default => ""
		};
	}

	static private function FinanceReceipt(TimelineEntry $entry): string
	{
		if (
			!is_null($entry->json) &&
			array_key_exists("id", $entry->json) &&
			array_key_exists("value", $entry->json)
		) {
			return "استلام مبلغ`{$entry->json['value']}` بايصال رقم `{$entry->json['id']}`<br />";
		}
		return "";
	}

	static private function FinancePayment(TimelineEntry $entry): string
	{
		if (
			!is_null($entry->json) &&
			array_key_exists("id", $entry->json) &&
			array_key_exists("value", $entry->json)
		) {
			return "دفع مبلغ `{$entry->json['value']}` بموجب ايصال رقم `{$entry->json['id']}`<br />";
		}
		return "";
	}
}



