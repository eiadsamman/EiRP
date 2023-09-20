<?php
namespace System\Personalization;

enum Identifiers: int
{
	
	case SystemFrequentVisit = 101;
	case SystemWorkingAccount = 102;
	case SystemWorkingCompany = 103;
	case SystemUserBookmark = 104;

	case SystemCountAccountSelection = 105;
	case SystemCountCompanySelection = 106;
	case SystemProductiontrackMaterial = 107;
	
	case SystemCountAccountOperation = 108;

	case SystemProductiontrackSection = 109;

	case AccountCustomePerpage = 201;
	case AccountCustomeQuerySave = 202;



}