<?php

declare(strict_types=1);

namespace System\Controller\Finance\Term;

enum Asset: int
{
	use \System\Core\EnumLib;
	case Cash = 10000;
	case Inventory = 10010;
	case Checks = 10020;
	case AccountsReceivable = 10030;
	case PrepaidExpenses = 10040;
	case AssestHeldForSale = 10060;


	case Equipments = 15070;
	case EquipmentsAccumulatedDepreciation = 15080;
	case OfficeEquipments = 15090;
	case AccumulatedDepreciation = 15100;

	public function termType(): string
	{
		return "Asset";
	}
}
