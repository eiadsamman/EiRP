<?php

$rl["A001"] = array(
	"from" => "data",
	"id" => array("bom_id" => "bom_id"),
	"value" => array("bom_beipn" => "bom_beipn", "bom_sapno" => "bom_sapno"),
	"select" => array("bom_beipn" => "bom_beipn", "bom_sapno" => "bom_sapno"),
	"details" => array("bom_mattype" => "bom_mattype", "bom_sapdesc" => "bom_sapdesc"),
	"search" => array('bom_beipn' => 'bom_beipn', 'bom_sapno' => 'bom_sapno', 'bom_sapdesc' => 'bom_sapdesc', 'bom_mattype' => 'bom_mattype'),
	"where" => " bom_mattype <> 'ZHLB' AND bom_mattype <> 'ZUNF' ",
	"group" => "",
);

$rl["A002"]          = $rl["A001"];
$rl["A002"]["where"] = " bom_mattype = 'ZHLB' ";

$rl["A003"]          = $rl["A001"];
$rl["A003"]["where"] = " bom_mattype = 'ZUNF' ";


$rl["A004"]                 = $rl["A001"];
$rl["A004"]["value"] = array("bom_sapdesc" => "bom_sapdesc");
$rl["A004"]["id"]    = array("bom_id" => "bom_id");
$rl["A004"]["where"]        = " ";



$rl["PAGEFILE"] = array(
	"from" => "pagefile 
		JOIN pagefile_language ON pfl_trd_id=trd_id AND pfl_lng_id=1 
		JOIN 
			pagefile_permissions ON pfp_trd_id=trd_id AND pfp_per_id={$app->user->info->permissions}",
	"id" => array("trd_directory" => "trd_directory"),
	"value" => array("pfl_value" => "pfl_value"),
	"select" => array("pfl_value" => "pfl_value"),
	"details" => array("trd_id" => "trd_id", "trd_directory" => "trd_directory"),
	"search" => array("pfl_value" => "pfl_value", "trd_directory" => "trd_directory", "trd_id" => "trd_id"),
	"where" => " trd_enable=1 AND trd_visible=1",
	"group" => "",
	"order" => array("pfl_value")
);


$rl["B001"] = array(
	"from" => "users JOIN permissions ON usr_privileges = per_id ",
	"id" => array("usr_id" => "usr_id"),
	"value" => array("usr_username" => "usr_username", "username" => "CONCAT_WS(' ',COALESCE(usr_firstname,''),COALESCE(usr_lastname,'')) "),
	"select" => array("userinfo" => "CONCAT(usr_id,\": \",usr_username)"),
	"details" => array("username_exteneded" => "   CONCAT( \"[\", per_title , \"] \" , CONCAT_WS(' ', COALESCE(usr_firstname,''),COALESCE(usr_lastname,'')) )    "),
	"search" => array('usr_firstname' => 'usr_firstname', 'usr_lastname' => 'usr_lastname', 'usr_username' => 'usr_username', 'usr_id' => 'usr_id'),
	"where" => " (per_order < {$app->user->info->level} or usr_id= {$app->user->info->id} ) ",
	"group" => "",
);


$rl["USERS"] = array(
	"from" => "users",
	"id" => array("usr_id" => "usr_id"),
	"value" => array("usr_username" => "CONCAT_WS(' ',COALESCE(usr_firstname,''),COALESCE(usr_lastname,'')) "),
	"select" => array("usr_username" => "CONCAT_WS(' ',COALESCE(usr_firstname,''),COALESCE(usr_lastname,'')) "),
	"details" => array(),
	"search" => array('usr_firstname' => 'usr_firstname', 'usr_lastname' => 'usr_lastname', 'usr_username' => 'usr_username', 'usr_id' => 'usr_id'),
	"where" => " " . ($app->user->info->id != 1 ? "  usr_id!=1 " : "") . "",
	"group" => " ",
	"order" => array("usr_id"),
	"union" => " UNION SELECT '[system]' AS usr_usrname ,0 AS usr_id ",

);



$rl["B002"] = array(
	"from" => "users JOIN labour ON lbr_id=usr_id",
	"id" => array("usr_id" => "usr_id"),
	"value" => array("usr_username" => "usr_username"),
	"select" => array("usr_firstname" => "usr_firstname", "usr_lastname" => "usr_lastname", "usr_username" => "usr_username"),
	"details" => array(),
	"search" => array('usr_firstname' => 'usr_firstname', 'usr_lastname' => 'usr_lastname', 'usr_username' => 'usr_username'),
	"where" => "  lbr_resigndate IS NULL ",
	"group" => "",
);
$rl["B003"] = array(
	"from" => "users JOIN labour ON lbr_id=usr_id ",
	"id" => array("usr_id" => "usr_id"),
	"value" => array("usr_firstname" => "usr_firstname", "usr_lastname" => "usr_lastname"),
	"select" => array("username" => "CONCAT_WS(' ',COALESCE(usr_firstname,''),COALESCE(usr_lastname,'')) "),
	"details" => array(),
	"search" => array('usr_firstname' => 'usr_firstname', 'usr_lastname' => 'usr_lastname', 'usr_id' => 'usr_id'),
	"where" => "  lbr_resigndate IS NULL ",
	"group" => "",
);
$rl["B00S"] = array(
	"from" => "users JOIN labour ON lbr_id=usr_id  ",
	"id" => array("usr_id" => "usr_id"),
	"value" => array("usr_firstname" => "usr_firstname", "usr_lastname" => "usr_lastname"),
	"select" => array("username" => "CONCAT_WS(' ',COALESCE(usr_firstname,''),COALESCE(usr_lastname,'')) "),
	"details" => array(),
	"search" => array('usr_firstname' => 'usr_firstname', 'usr_lastname' => 'usr_lastname', 'usr_id' => 'usr_id'),
	"where" => " usr_entity=" . (int) $app->user->company->id,
	"group" => "",
);



$rl["C001"] = array(
	"from" => "permissions",
	"id" => array("per_id" => "per_id"),
	"value" => array("per_title" => "per_title"),
	"select" => array("per_title" => "per_title"),
	"details" => array(),
	"search" => array('per_title' => 'per_title'),
	"where" => "",
	"group" => "",
	"order" => array("per_id")
);

$rl["PERM_LEVEL"] = array(
	"from" => "permissions",
	"id" => array("per_id" => "per_id"),
	"value" => array("per_title" => "per_title"),
	"select" => array("per_title" => "per_title"),
	"details" => array(),
	"search" => array('per_title' => 'per_title'),
	"where" => " per_order < {$app->user->info->level} ",
	"group" => "",
	"order" => array("per_id")
);



$rl["D002"] = array(
	"from" => "`acc_accounts` 
				JOIN companies ON comp_id = prt_company_id
				JOIN currencies ON cur_id = prt_currency
				",
	"id" => array("prt_id" => "prt_id"),
	"value" => array("zname" => "CONCAT('[',cur_shortname,'] ', CONCAT_WS(': ', comp_name, prt_name))"),
	"select" => array("zname" => "CONCAT('[',cur_shortname,']', CONCAT_WS(': ', comp_name, prt_name))"),
	"details" => array(),
	"search" => array("prt_name" => "prt_name", "comp_name" => "comp_name", "cur_shortname" => "cur_shortname", "cur_name" => "cur_name"),
	"where" => "",
	"group" => "",
);

$rl["E001"]  = array(
	"from" => "labour_section",
	"id" => array("lsc_id" => "lsc_id"),
	"value" => array("lsc_name" => "lsc_name"),
	"select" => array("lsc_name" => "lsc_name"),
	"details" => array(),
	"search" => array("lsc_name" => "lsc_name"),
	"where" => "",
	"group" => "",
);
$rl["E002"]  = array(
	"from" => "labour_type JOIN labour_section ON lsc_id=lty_section",
	"id" => array("lty_id" => "lty_id"),
	"value" => array('name' => 'CONCAT(lsc_name,", ",lty_name)'),
	"select" => array('lsc_name' => 'lsc_name', 'lty_name' => 'lty_name'),
	"details" => array(),
	"search" => array('lty_name' => 'lty_name', 'lsc_name' => 'lsc_name'),
	"where" => "",
	"group" => "",
);
$rl["E002A"] = array(
	"from" => "labour_type JOIN labour_section ON lsc_id=lty_section",
	"id" => array("lty_id" => "lty_id"),
	"value" => array('name' => 'CONCAT(lsc_name,", ",lty_name)'),
	"select" => array('lsc_name' => 'lsc_name', 'lty_name' => 'lty_name'),
	"details" => array(),
	"search" => array('lty_name' => 'lty_name', 'lsc_name' => 'lsc_name'),
	"where" => "",
	"group" => "",
);

$rl["E003"] = array(
	"from" => "labour_shifts",
	"id" => array("lsf_id" => "lsf_id"),
	"value" => array("lsf_name" => "lsf_name"),
	"select" => array("lsf_name" => "lsf_name"),
	"details" => array(),
	"search" => array("lsf_name" => "lsf_name"),
	"where" => "",
	"group" => "",
);

$rl["E004"] = array(
	"from" => "labour_residentail",
	"id" => array("ldn_id" => "ldn_id"),
	"value" => array("ldn_name" => "ldn_name"),
	"select" => array("ldn_name" => "ldn_name"),
	"details" => array(),
	"search" => array("ldn_name" => "ldn_name"),
	"where" => "",
	"group" => "",
);

$rl["E005"] = array(
	"from" => "
		labour 
			JOIN users ON usr_id=lbr_id 
			LEFT JOIN labour_type ON usr_jobtitle = lty_id 
			LEFT JOIN labour_shifts ON lsf_id = lbr_shift",
	"id" => array("usr_id" => "usr_id"),
	"value" => array("usr_firstname" => "usr_firstname", "usr_lastname" => "usr_lastname"),
	"select" => array("usr_firstname" => "usr_firstname", "usr_lastname" => "usr_lastname"),
	"details" => array(),
	"search" => array('usr_lastname' => 'usr_lastname', 'usr_firstname' => 'usr_firstname', "lsf_name" => "lsf_name", "lty_name" => "lty_name"),
	"where" => " lbr_resigndate IS NULL ",
	"group" => "",
);


$rl["G000"] = array(
	"from" => "gender",
	"id" => array("gnd_id" => "gnd_id"),
	"value" => array("gnd_name" => "gnd_name"),
	"select" => array("gnd_name" => "gnd_name"),
	"details" => array(),
	"search" => array("gnd_name" => "gnd_name"),
	"where" => "",
	"group" => "",
);


$rl["BIRTHDATE"] = array(
	"from" => "(
		SELECT 

			 (curdate() - INTERVAL (a.a + (10 * b.a) + (100 * c.a) + (1000 * d.a) + (10000 * e.a)) DAY) as Date,
			(DATE_FORMAT((curdate() - INTERVAL (a.a + (10 * b.a) + (100 * c.a) + (1000 * d.a) + (10000 * e.a)) DAY),'%e %M, %Y')) AS formated2,
			(DATE_FORMAT((curdate() - INTERVAL (a.a + (10 * b.a) + (100 * c.a) + (1000 * d.a) + (10000 * e.a)) DAY),'%d %M, %Y')) AS formated
		FROM 
			(SELECT 
				0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as a
				cross join (select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as b
				cross join (select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as c
				cross join (select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as d
				cross join (select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as e
				
		) a",
	"id" => array("Date" => "a.Date"),
	"value" => array("formated" => "a.formated"),
	"select" => array("formated" => "a.formated"),
	"details" => array(),
	"search" => array("Date" => "a.Date", "formated" => "a.formated", "formated2" => "a.formated2"),
	"where" => " (a.Date BETWEEN DATE_SUB(NOW(), INTERVAL 70 YEAR) AND DATE_SUB(NOW(), INTERVAL 10 YEAR)) ",
	"group" => "",
);



$rl["DATE"] = array(
	"from" => "
		(SELECT 
						 ( curdate() + INTERVAL (a.a + (10 * b.a) + (100 * c.a) + (1000 * d.a) - (366*10)) DAY) as Date,
			(DATE_FORMAT(( curdate() + INTERVAL (a.a + (10 * b.a) + (100 * c.a) + (1000 * d.a) - (366*10)) DAY),'%Y-%m-%d')) AS formated,
			(DATE_FORMAT(( curdate() + INTERVAL (a.a + (10 * b.a) + (100 * c.a) + (1000 * d.a) - (366*10)) DAY),'%Y-%c-%e')) AS search,
			(DATE_FORMAT(( curdate() + INTERVAL (a.a + (10 * b.a) + (100 * c.a) + (1000 * d.a) - (366*10)) DAY),'%M%Y')) AS search2
			
		FROM
			(SELECT 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as a
		   	cross join (select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as b
			cross join (select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as c
			cross join (select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as d
	
		) a",
	"id" => array("Date" => "a.Date"),
	"value" => array("formated" => "a.formated"),
	"select" => array("formated" => "a.formated"),
	"details" => array(),
	"search" => array("search" => "a.search", "search2" => "a.search2", "Date" => "a.Date"),
	"where" => " (a.Date BETWEEN '" . date("Y-m-d", mktime(0, 0, 0, date('m'), date('d'), date('Y') - 10)) . "' AND '" . date("Y-m-d", mktime(0, 0, 0, date('m'), date('d'), date('Y') + 10)) . "') ",
	"group" => "",
);


$rl["WIDE_DATE"] = array(
	"from" => "
		(SELECT 
			( curdate()  + INTERVAL (a.a + (10 * b.a) + (100 * c.a) + (1000 * d.a) - (366*10)) DAY) as Date,
			(DATE_FORMAT(( curdate() + INTERVAL (a.a + (10 * b.a) + (100 * c.a) + (1000 * d.a) - (366*10)) DAY),'%Y-%m-%d')) AS formated2,
			(DATE_FORMAT(( curdate() + INTERVAL (a.a + (10 * b.a) + (100 * c.a) + (1000 * d.a) - (366*10)) DAY),'%Y-%m-%d')) AS formated
		FROM
			(SELECT 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as a
		   	cross join (select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as b
			cross join (select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as c
			cross join (select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as d
	
		) a",
	"id" => array("Date" => "a.Date"),
	"value" => array("formated" => "a.formated"),
	"select" => array("Date" => "a.Date"),
	"details" => array(),
	"search" => array("Date" => "a.Date", "formated" => "a.formated", "formated2" => "a.formated2"),
	"where" => " (a.Date BETWEEN '" . date("Y-m-d", mktime(0, 0, 0, date('m'), date('d'), date('Y') - 10)) . "' AND '" . date("Y-m-d", mktime(0, 0, 0, date('m'), date('d'), date('Y') + 10)) . "') ",
	"group" => "",
);


$rl["DATE_MONTH_BACK"] = array(
	"from" => "(
		SELECT 
			('" . date("Y-m-d", mktime(0, 0, 0, date("m") - 1, 1, date("Y"))) . "' + INTERVAL (a.a + (10 * b.a) + (100 * c.a) + (1000 * d.a)) DAY) as select_date,
			(DATE_FORMAT(('" . date("Y-m-d", mktime(0, 0, 0, date("m") - 1, 1, date("Y"))) . "' + INTERVAL (a.a + (10 * b.a) + (100 * c.a) + (1000 * d.a)) DAY),'%Y-%m-%d')) AS formated
		FROM 
			(SELECT 
				0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as a
				cross join (select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as b
				cross join (select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as c
				cross join (select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as d
		) a",
	"id" => array("select_date" => "a.select_date"),
	"value" => array("formated" => "a.formated"),
	"select" => array("select_date" => "a.select_date"),
	"details" => array(),
	"search" => array("select_date" => "a.select_date", "formated" => "a.formated"),
	"where" => "  ",
	"group" => "",
);


$rl["YEAR"] = array(
	"from" => "(
		SELECT 
			(DATE_FORMAT((curdate() - INTERVAL (a.a + (10 * b.a) + (100 * c.a)) YEAR),'%Y-01-01')) as Date,
			(DATE_FORMAT((curdate() - INTERVAL (a.a + (10 * b.a) + (100 * c.a)) YEAR),'%Y')) AS formated
		FROM 
			(SELECT 
				0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as a
				cross join (select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as b
				cross join (select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as c
		) a",
	"id" => array("Date" => "a.Date"),
	"value" => array("formated" => "a.formated"),
	"select" => array("formated" => "a.formated"),
	"details" => array(),
	"search" => array("Date" => "a.Date", "formated" => "a.formated"),
	"where" => " (a.Date BETWEEN '" . date("Y-m-d", mktime(0, 0, 0, 1, 1, date('Y') - 10)) . "' AND '" . date("Y-m-d", mktime(0, 0, 0, 1, 1, date('Y') + 10)) . "') ",
	"group" => "",
);


$rl["MONTH"] = array(
	"from" => "(
		SELECT 
			(DATE_FORMAT((curdate() + INTERVAL (a.a + (10 * b.a) + (100 * c.a) - (50)) MONTH),'%Y-%m-01')) as Date,
			(DATE_FORMAT((curdate() + INTERVAL (a.a + (10 * b.a) + (100 * c.a) - (50)) MONTH),'%Y-%m')) AS formated
		FROM 
			(SELECT 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as a
		   	cross join (select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as b
			cross join (select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as c
		) a",
	"id" => array("Date" => "a.Date"),
	"value" => array("formated" => "a.formated"),
	"select" => array("formated" => "a.formated"),
	"details" => array(),
	"search" => array("Date" => "a.Date", "formated" => "a.formated"),
	"where" => " (a.Date BETWEEN '" . date("Y-m-d", mktime(0, 0, 0, date('m'), date('d'), date('Y') - 10)) . "' AND '" . date("Y-m-d", mktime(0, 0, 0, date('m'), date('d'), date('Y') + 10)) . "') ",
	"group" => "",
);



$rl["ACC_CATGRP"] = array(
	"from" => " acc_categorygroups ",
	"id" => array("accgrp_id" => "accgrp_id"),
	"value" => array("accgrp_name" => "accgrp_name"),
	"select" => array("accgrp_name" => "accgrp_name"),
	"details" => array(),
	"search" => array("accgrp_name" => "accgrp_name"),
	"where" => " ",
	"group" => "",
	"order" => array("accgrp_name")
);


$rl["ACC_CAT"] = array(
	"from" => " acc_categories JOIN acc_categorygroups ON accgrp_id=acccat_group LEFT JOIN acc_main ON acccat_id=acm_category ",
	"id" => array("acccat_id" => "acccat_id"),
	"value" => array("name" => " CONCAT(accgrp_name,\": \",acccat_name) "),
	"select" => array("name" => " CONCAT(accgrp_name,\": \",acccat_name) ", "bensum" => "count(acccat_name)"),
	"details" => array(),
	"search" => array("acccat_name" => "acccat_name", "accgrp_name" => "accgrp_name"),
	"where" => " ",
	"group" => " acccat_name ",
	"order" => array("bensum DESC"),
	"hide" => array("bensum")
);



$rl["ACC_788"] = array(
	"from" => " `acc_accounts`  
		JOIN currencies ON cur_id=prt_currency
		JOIN user_partition ON prt_id=upr_prt_id AND upr_usr_id={$app->user->info->id} AND upr_prt_fetch=1
		JOIN (
			SELECT 
				comp_id,comp_name 
			FROM 
				companies 
					JOIN user_company ON comp_id=urc_usr_comp_id AND urc_usr_id={$app->user->info->id}
					JOIN user_settings ON usrset_usr_id={$app->user->info->id} AND usrset_type=" . \System\Controller\Personalization\Identifiers::SystemWorkingCompany->value . " AND usrset_usr_defind_name='UNIQUE' AND usrset_value=comp_id
			) AS _companies ON prt_company_id=_companies.comp_id
			
		LEFT JOIN user_settings ON usrset_usr_defind_name=prt_id AND usrset_usr_id={$app->user->info->id} AND usrset_type = " . \System\Controller\Personalization\Identifiers::SystemCountAccountSelection->value . "
		",
	"id" => array("prt_id" => "prt_id"),
	"value" => array("name" => " CONCAT (\"[\", cur_shortname , \"] \" , _companies.comp_name, \": \", prt_name)"),
	"select" => array("name" => " CONCAT (\"[\", cur_shortname , \"] \" , _companies.comp_name, \": \", prt_name)"),
	"details" => array(),
	"search" => array("prt_name" => "prt_name", "cur_name" => "cur_name", "cur_shortname" => "cur_shortname", "comp_name" => "_companies.comp_name"),
	"where" => " " . (isset($_POST['exclude']) ? " prt_id!= {$_POST['exclude']} " : "") . " upr_usr_id={$app->user->info->id} ",
	"group" => "",
	"order" => array("(usrset_value + 0) DESC")
);




$rl["ACC_VIEW"] = array(
	"from" => " `acc_accounts`  
		JOIN currencies ON cur_id=prt_currency
		JOIN user_partition ON prt_id=upr_prt_id AND upr_usr_id={$app->user->info->id} AND upr_prt_view=1
		JOIN companies ON prt_company_id=comp_id
		LEFT JOIN user_settings ON usrset_usr_defind_name=prt_id AND usrset_usr_id={$app->user->info->id} AND usrset_type=" . \System\Controller\Personalization\Identifiers::SystemCountAccountOperation->value . "
		",
	"id" => array("prt_id" => "prt_id"),
	"value" => array("name" => " CONCAT (\"[\", cur_shortname , \"] \" , comp_name, \": \", prt_name)"),
	"select" => array("name" => " CONCAT (\"[\", cur_shortname , \"] \" , comp_name, \": \", prt_name)"),
	"details" => array(),
	"search" => array("prt_name" => "prt_name", "cur_name" => "cur_name", "cur_shortname" => "cur_shortname", "comp_name" => "comp_name"),
	"where" => " " . (isset($_POST['exclude']) ? " prt_id!= {$_POST['exclude']} " : "") . " upr_usr_id={$app->user->info->id} ",
	"group" => "",
	"order" => array("(usrset_value+0) DESC")
);











$rl["ACC_OUTBOUND"] = array(
	"from" => " `acc_accounts`  
		JOIN currencies ON cur_id=prt_currency
		JOIN user_partition ON prt_id=upr_prt_id AND upr_usr_id={$app->user->info->id} AND upr_prt_outbound=1
		JOIN companies ON prt_company_id=comp_id
		LEFT JOIN user_settings ON usrset_usr_defind_name=prt_id AND usrset_usr_id={$app->user->info->id} AND usrset_type=" . \System\Controller\Personalization\Identifiers::SystemCountAccountOperation->value . "
		",
	"id" => array("prt_id" => "prt_id"),
	"value" => array("name" => " CONCAT (\"[\", cur_shortname , \"] \" , comp_name, \": \", prt_name)"),
	"select" => array("name" => " CONCAT (\"[\", cur_shortname , \"] \" , comp_name, \": \", prt_name)"),
	"details" => array(),
	"search" => array("prt_name" => "prt_name", "cur_name" => "cur_name", "cur_shortname" => "cur_shortname", "comp_name" => "comp_name"),
	"where" => " " . (isset($_POST['exclude']) ? " prt_id!= {$_POST['exclude']} AND" : "") . "
				" . (isset($_POST['company']) ? " comp_id= " . (int) $_POST['company'] . " AND " : "") . " 
				upr_usr_id={$app->user->info->id} ",
	"group" => "",
	"order" => array("(usrset_value+0) DESC")
);





$rl["ACC_ALL"] = array(
	"from" => " `acc_accounts`  
		JOIN currencies ON cur_id=prt_currency
		JOIN companies ON prt_company_id=comp_id
		",
	"id" => array("prt_id" => "prt_id"),
	"value" => array("name" => " CONCAT (\"[\", cur_shortname , \"] \" , comp_name, \": \", prt_name)"),
	"select" => array("name" => " CONCAT (\"[\", cur_shortname , \"] \" , comp_name, \": \", prt_name)"),
	"details" => array(),
	"search" => array("prt_name" => "prt_name", "cur_name" => "cur_name", "cur_shortname" => "cur_shortname", "comp_name" => "comp_name"),
	"where" => "",
	"group" => "",
	"order" => array("comp_name", "prt_name", "cur_shortname")
);



$rl["CURRENCY"] = array(
	"from" => " currencies ",
	"id" => array("cur_id" => "cur_id"),
	"value" => array("name" => " CONCAT(cur_name,\" (\",cur_symbol,\")\")"),
	"select" => array("name" => " CONCAT(cur_name,\" (\",cur_symbol,\")\")"),
	"details" => array(),
	"search" => array("cur_name" => "cur_name"),
	"where" => " ",
	"group" => "",
	"order" => array("cur_id")
);


$rl["CURRENCY_SYMBOL"] = array(
	"from" => " currencies ",
	"id" => array("cur_id" => "cur_id"),
	"value" => array("cur_shortname" => "cur_shortname"),
	"select" => array("cur_shortname" => "cur_shortname"),
	"details" => array(),
	"search" => array("cur_name" => "cur_name", "cur_symbol" => "cur_symbol", "cur_shortname" => "cur_shortname"),
	"where" => " ",
	"group" => "",
	"order" => array("cur_name")
);



$rl["ACC_REFERENCE"] = array(
	"from" => " acc_main ",
	"id" => array("name" => "DISTINCT acm_reference"),
	"value" => array("name" => "DISTINCT acm_reference"),
	"select" => array("name" => "DISTINCT acm_reference"),
	"details" => array(),
	"search" => array("acm_reference" => "acm_reference"),
	"where" => " ",
	"group" => "",
);




$rl["ACC_EDITORS"] = array(
	"from" => " users JOIN acc_main ON acm_editor_id=usr_id ",
	"id" => array("usr_id" => "usr_id"),
	"value" => array("editorname" => "DISTINCT CONCAT_WS(' ',COALESCE(usr_firstname,''),COALESCE(usr_lastname,''))"),
	"select" => array("editorname" => "DISTINCT CONCAT_WS(' ',COALESCE(usr_firstname,''),COALESCE(usr_lastname,''))"),
	"details" => array(),
	"search" => array("usr_firstname" => "usr_firstname", "usr_lastname" => "usr_lastname"),
	"where" => "",
	"group" => "",
);




$rl["HIR_LABOUR"] = array(
	"from" => "
		labour 
			JOIN users  ON lbr_id=usr_id 
			JOIN job_hierarchyroles ON jhr_job_id = usr_jobtitle
			",
	"id" => array("usr_id" => "usr_id"),
	"value" => array("usr_firstname" => "usr_firstname", "usr_lastname" => "usr_lastname"),
	"select" => array("username" => "CONCAT_WS(' ',COALESCE(usr_firstname,''),COALESCE(usr_lastname,'')) "),
	"details" => array(),
	"search" => array('usr_firstname' => 'usr_firstname', 'usr_lastname' => 'usr_lastname', 'usr_id' => 'usr_id', ),
	"where" => " " . (isset($_POST['hir']) ? " jhr_hir_id=" . ((int) $_POST['hir']) . " " : " jhr_job_id=0 ") . " AND lbr_resigndate IS NULL ",
	"group" => " ",
);


$rl["ABSENCE_TYPE"] = array(
	"from" => "absence_types",
	"id" => array("abs_typ_id" => "abs_typ_id"),
	"value" => array("abs_typ_name" => "abs_typ_name"),
	"select" => array("abs_typ_name" => "abs_typ_name"),
	"details" => array(),
	"search" => array("abs_typ_name" => "abs_typ_name"),
	"where" => " ",
	"group" => " ",
);


$rl["WORKING_TIMES"] = array(
	"from" => "workingtimes",
	"id" => array("lwt_id" => "lwt_id"),
	"value" => array("lwt_name" => "lwt_name"),
	"select" => array("lwt_name" => "lwt_name"),
	"details" => array(),
	"search" => array("lwt_name" => "lwt_name"),
	"where" => " ",
	"group" => " ",
	"order" => array("lwt_value")
);


$rl["MAT_TYPE"] = array(
	"from" => "mat_materialtype",
	"id" => array("mattyp_id" => "mattyp_id"),
	"value" => array("mattyp_name" => "mattyp_name"),
	"select" => array("mattyp_name" => "mattyp_name"),
	"details" => array("mattyp_description" => "mattyp_description"),
	"search" => array("mattyp_name" => "mattyp_name", "mattyp_description" => "mattyp_description"),
	"where" => " ",
	"group" => " ",
	"order" => array("mattyp_id")
);


$rl["COMPANIES"] = array(
	"from" => "companies JOIN user_company ON urc_usr_comp_id = comp_id AND urc_usr_id = {$app->user->info->id}",
	"id" => array("comp_id" => "comp_id"),
	"value" => array("comp_name" => "comp_name"),
	"select" => array("comp_name" => "comp_name"),
	"details" => array(),
	"search" => array("comp_name" => "comp_name"),
	"where" => " ",
	"group" => " ",
	"order" => array("comp_name")
);

$rl["COMPANIES_ALL"] = array(
	"from" => "companies",
	"id" => array("comp_id" => "comp_id"),
	"value" => array("comp_name" => "comp_name"),
	"select" => array("comp_name" => "comp_name"),
	"details" => array(),
	"search" => array("comp_name" => "comp_name"),
	"where" => " ",
	"group" => " ",
	"order" => array("comp_name")
);


$rl["EAN"] = array(
	"from" => "mat_ean",
	"id" => array("ean_id" => "ean_id"),
	"value" => array("ean_value" => "ean_value"),
	"select" => array("ean_value" => "ean_value"),
	"details" => array(),
	"search" => array("ean_value" => "ean_value"),
	"where" => "  ean_lock IS NULL ",
	"group" => " ",
	"order" => array("ean_value")
);



$rl["MAT_CATEGORY_GROUP"] = array(
	"from" => "mat_categorygroup",
	"id" => array("matcatgrp_id" => "matcatgrp_id"),
	"value" => array("matcatgrp_name" => "matcatgrp_name"),
	"select" => array("matcatgrp_name" => "matcatgrp_name"),
	"details" => array(),
	"search" => array("matcatgrp_name" => "matcatgrp_name"),
	"where" => " ",
	"group" => " ",
	"order" => array("matcatgrp_name")
);

$rl["MAT_CATEGORY"] = array(
	"from" => "mat_category LEFT JOIN mat_categorygroup ON matcatgrp_id = matcat_matcatgrp_id",

	"id" => array("matcat_id" => "matcat_id"),
	"value" => array("r_name" => "CONCAT_WS(\", \", matcatgrp_name, matcat_name)"),

	"select" => array("r_name" => "CONCAT_WS(\", \", matcatgrp_name, matcat_name)"),

	"details" => array(),
	"search" => array("matcatgrp_name" => "matcatgrp_name", "matcat_name" => "matcat_name"),
	"where" => " ",
	"group" => " ",
	"order" => array("matcatgrp_name", "matcat_name")
);




$rl["CALENDAR_OPERATIONS"] = array(
	"from" => "calendar_operations",
	"id" => array("cop_id" => "cop_id"),
	"value" => array("cop_name" => "cop_name"),
	"select" => array("cop_name" => "cop_name"),
	"details" => array(),
	"search" => array("cop_name" => "cop_name"),
	"where" => " ",
	"group" => " ",
	"order" => array("cop_name")
);

$rl["TRANSPORTATION"] = array(
	"from" => "labour_transportation",
	"id" => array("trans_id" => "trans_id"),
	"value" => array("trans_name" => "trans_name"),
	"select" => array("trans_name" => "trans_name"),
	"details" => array("details" => "CONCAT_WS(\", \",CONCAT('Capacity: ',trans_capacity),IF(NULLIF(trans_plate, '') IS NULL, NULL, trans_plate))"),
	"search" => array("trans_name" => "trans_name"),
	"where" => " ",
	"group" => " ",
	"order" => array("trans_name")
);


$rl["BOM"] = array(
	"from" => "
		mat_materials 
			JOIN mat_materialtype ON mattyp_id=mat_mattyp_id  
			LEFT JOIN 
				(
					SELECT 
						CONCAT_WS(\", \", matcatgrp_name, matcat_name) AS cat_alias , matcat_id 
					FROM 
						mat_category LEFT JOIN mat_categorygroup ON matcat_matcatgrp_id = matcatgrp_id
				) AS _category ON mat_matcat_id=_category.matcat_id
			",
	"id" => array("mat_id" => "mat_id"),
	"value" => array("mat_long_id" => "mat_long_id", "cat_alias" => "cat_alias", "mat_name" => "mat_name"),

	"select" => array("cat_alias" => "cat_alias", "mat_name" => "mat_name"),
	"details" => array("mattyp_description" => "mattyp_description", "mat_date" => "mat_date"),
	"search" => array("mat_id" => "mat_id", "mat_name" => "mat_name", "mat_longname" => "mat_longname", "mat_date" => "mat_date", "mat_long_id" => "mat_long_id", "cat_alias" => "cat_alias", "mattyp_name" => "mattyp_name"),
	"params" => ["unitsystem" => "mat_unitsystem"],
	"where" => " ",
	"group" => " ",
	"order" => array("mattyp_id","mat_name")
);


$rl["ABS_STARTS"] = array(
	"from" => "absence_starts",
	"id" => array("abs_srt_id" => "abs_srt_id"),
	"value" => array("abs_srt_name" => "abs_srt_name"),
	"select" => array("abs_srt_name" => "abs_srt_name"),
	"details" => array(),
	"search" => array('abs_srt_name' => 'abs_srt_name'),
	"where" => "",
	"group" => "",
	"order" => array("abs_srt_name")
);

$rl["BUSINESS_FIELD"] = array(
	"from" => "business_field",
	"id" => array("bisfld_id" => "bisfld_id"),
	"value" => array("bisfld_name" => "bisfld_name"),
	"select" => array("bisfld_name" => "bisfld_name"),
	"details" => array(),
	"search" => array('bisfld_name' => 'bisfld_name', 'bisfld_category' => 'bisfld_category'),
	"where" => "",
	"group" => "",
	"order" => array("bisfld_name")
);

$rl["SALARY_PAYMENT_METHOD"] = array(
	"from" => "labour_method",
	"id" => array("lbr_mth_id" => "lbr_mth_id"),
	"value" => array("lbr_mth_name" => "lbr_mth_name"),
	"select" => array("lbr_mth_name" => "lbr_mth_name"),
	"details" => array(),
	"search" => array('lbr_mth_name' => 'lbr_mth_name'),
	"where" => "",
	"group" => "",
	"order" => array("lbr_mth_id")
);


$rl["COUNTRIES"] = array(
	"from" => "countries",
	"id" => array("cntry_id" => "cntry_id"),
	"value" => array("cntry_name" => "cntry_name"),
	"select" => array("cntry_name" => "cntry_name"),
	"details" => array(),
	"search" => array('cntry_name' => 'cntry_name', 'cntry_code' => 'cntry_code', 'cntry_abrv' => 'cntry_abrv'),
	"where" => "",
	"group" => "",
	"order" => array("cntry_name")
);



$rl["COMPANY"] = array(
	"from" => "companies",
	"id" => array("comp_id" => "comp_id"),
	"value" => array("comp_name" => "comp_name"),
	"select" => array("comp_name" => "comp_name"),
	"details" => array(),
	"search" => array('comp_name' => 'comp_name'),
	"where" => " " . (isset($_POST['sloexcludecompany']) ? " comp_id!=" . ((int) $_POST['sloexcludecompany']) . " " : " ") . " ",
	"group" => "",
	"order" => array("comp_id")
);


$rl["COMPANY_USER"] = array(
	"from" => "companies 
				JOIN user_company ON urc_usr_comp_id=comp_id AND urc_usr_id={$app->user->info->id}
				LEFT JOIN user_settings ON usrset_usr_defind_name=comp_id AND usrset_usr_id={$app->user->info->id} AND usrset_type = " . \System\Controller\Personalization\Identifiers::SystemCountCompanySelection->value . " ",
	"id" => array("comp_id" => "comp_id"),
	"value" => array("comp_name" => "comp_name"),
	"select" => array("comp_name" => "comp_name"),
	"details" => array(),
	"search" => array('comp_name' => 'comp_name'),
	"where" => "",
	"group" => "",
	"order" => array("(usrset_value+0) DESC")
);




$rl["BRANDS"] = array(
	"from" => "brands",
	"id" => array("brand_id" => "brand_id"),
	"value" => array("brand_name" => "brand_name"),
	"select" => array("brand_name" => "brand_name"),
	"details" => array(),
	"search" => array('brand_name' => 'brand_name'),
	"where" => "",
	"group" => "",
	"order" => array("brand_name")
);


$rl["COSTCENTER"] = array(
	"from" => "inv_costcenter",
	"id" => array("ccc_id" => "ccc_id"),
	"value" => array("ccc_name" => "ccc_name"),
	"select" => array("ccc_name" => "ccc_name"),
	"details" => array(),
	"search" => array('ccc_name' => 'ccc_name'),
	"where" => "",
	"group" => "",
	"order" => array("ccc_name")
);

$rl["COSTCENTER_USER"] = array(
	"from" => "inv_costcenter 
				JOIN user_costcenter ON ccc_id=usrccc_ccc_id AND usrccc_usr_id={$app->user->info->id}
				",
	"id" => array("ccc_id" => "ccc_id"),
	"value" => array("ccc_name" => "ccc_name"),
	"select" => array("ccc_name" => "ccc_name"),
	"details" => array(),
	"search" => array('ccc_name' => 'ccc_name'),
	"where" => "",
	"group" => "",
	"order" => array("ccc_name")
);
