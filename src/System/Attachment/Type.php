<?php
namespace System\Attachment;


enum Type: int
{

	case HrID = 190;
	case HrPerson = 189;
	case FinanceRecord = 188;

	case CompanyCommercialDoc = 251;
	case CompanyTaxDoc = 252;
	case CompanyVatDoc = 253;

	case CompanyLogo = 242;


	case Material = 243;
	
	case Timeline = 268;

	case BrandLogo = 271;
}
