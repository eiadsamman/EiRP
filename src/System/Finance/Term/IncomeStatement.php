<?php

declare(strict_types=1);

namespace System\Finance\Term;

enum IncomeStatement: int
{
	use \System\enumLib;

	case SalesRevenue = 40000;
	case ServiceRevenue = 40010;
	case InterestRevenue = 40020;
	case OtherRevenue = 40030;

	case Advertising = 45040;
	case BadDebt = 45050;
	case Commissions = 45060;
	case CostOfSales = 45070;
	case DepreciationExpense = 45080;
	case EmployeeBenefits = 45090;
	case FurnitureAndEquipment = 45100;
	case Insurance = 45110;
	case InterestExpense = 45120;
	case MaintenanceAndRepair = 45130;
	case OfficeSupplies = 45140;
	case ManugacturingSupplies = 45150;
	case PayrollTaxes = 45160;
	case RentPropertyTaxes = 45170;
	case ResearchAndDevelopment = 45180;
	case SalariesAndWages = 45190;
	case Software = 45200;
	case Travel = 45210;
	case Utilities = 45220;
	case WebHostingAndDomains = 45230;
	case Administration = 45240;
	case Other = 45250;

	public function range(): array
	{
		return [40000, 49999];
	}
	public function termType(): string
	{
		return "Income Statement";
	}
}