<?php
declare(strict_types=1);

namespace System\Controller\Personalization;

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
	
	case SystemDarkMode = 110;
	case SystemDashboard = 111;


	case AccountCustomePerpage = 201;
	case AccountCustomeQuerySave = 202;



}