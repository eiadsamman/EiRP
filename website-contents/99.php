<?php

use System\Finance\Accounting;
use System\SmartListObject;
use System\Template\Gremium;

define("TRANSACTION_ATTACHMENT_PAGEFILE", "188");

if (0) {
	echo "<pre>";
	$res = $app->db->query("SELECT * FROM acc_main WHERE acm_id = 7157");
	$row = $res->fetch_assoc();
	print_r($row);
	exit;
}

function replaceARABIC($str){
	$str=str_replace(["أ","إ","آ"],"[أإاآ]+",$str);
	$str=str_replace(["ة","ه"],"[ةه]+",$str);
	$str=str_replace(["ى","ي"],"[يى]+",$str);
	return $str;
}




$ajax_debug = false;
$debug_level = 3;
$accounts_comparition_style = " AND ";
$accounting = new Accounting($app);
$__systemdefaultcurrency = $accounting->system_default_currency();
$currency_list = $accounting->get_currency_list();
$default_perpage = 25;
$arr_overview = array("total" => 0, "sum" => 0);
$pre_load_variables = true;

if ($ajax_debug) {
	ini_set('xdebug.var_display_max_depth', 5);
	ini_set('xdebug.var_display_max_children', 256);
	ini_set('xdebug.var_display_max_data', 1024);
}



/*
Retreive user setting (per_page)
*/
$per_page = $default_perpage;
$r_perpage = $app->db->query("SELECT usrset_value FROM user_settings WHERE usrset_usr_id={$app->user->info->id} AND usrset_type = " . \System\Personalization\Identifiers::AccountCustomePerpage->value . ";");
if ($r_perpage) {
	if ($row_perpage = $r_perpage->fetch_assoc()) {
		$per_page = (int) $row_perpage['usrset_value'];
	}
}

/*
	Retreive the latest (non-array) branch children counts for a giver array
*/
function recursive_counter($arr)
{
	$cnt = 0;
	if (is_array($arr)) {
		foreach ($arr as $v) {
			if (is_array($v)) {
				$cnt += recursive_counter($v);
			} else {
				$cnt++;
			}
		}
	}
	return $cnt;
}
/*
	Retreive a given array branch by an array of keys
	Return:
		array branch
*/
function recursive_counting_bykey($arr_map, $keys)
{
	$a = $arr_map;
	while (count($keys) > 0) {
		$k = array_shift($keys);
		$a = &$a[$k];
	}
	return recursive_counter($a);
}
/*
	Build (rowspan table) from $arr_output + $arr_output_raw
	Schematic
	[0] => array(
			"name"=>name,
			[1]=>array(
				"name"=>name,
				[0]=>#,
				[3]=>#,
				[4]=>#,
				[5]=>#,
				[6]=>#,
			)
		)
	
	$v: children array list
	$map: the raw data array (no names only children and values)
	$max_depth: maximum depth of groups list
	$current_depth: pass through the current depth to the next recursive call
	$first_item: pass if this current node is the first of its own branch
*/
function layout_extrusion($array, $map, $keys, $max_depth, $current_depth, $first_item)
{
	if (is_array($array)) {
		foreach ($array as $k => $v) {
			//Count latest branch children for each given array branch
			$recursive_tree_depth = recursive_counting_bykey($map, $keys);

			if ($k === "name") {
				//Start new row for level 0 in the array
				if ($current_depth == 0 || $max_depth == 1) {
					echo "<tr>";
				}
				//Start new row for each first branch that is not the first child of its own array
				if ($first_item > 0) {
					echo "<tr>";
				}

				//Loop through given names of current array branch
				foreach ($v as $field) {

					echo "<td" . ($recursive_tree_depth > 1 ? " rowspan=\"$recursive_tree_depth\"" : "") . ">" . (trim($field ?? "") == "" ? "<i>[null]</i>" : $field) . "</td>";
				}
				//Cycle back through array branch children
				$first_item = -1;
			} elseif ($k !== "name" && !is_array($v)) {
				/*Print latest array node*/
				echo "<td>" . ($v < 0 ? "(" . number_format(abs($v), 2, ".", ",") . ")" : number_format($v, 2, ".", ",")) . "</td>";
				/*Close row for each latest array node*/
				echo "</tr>";
			}
			/*Cycle forward through array branch children*/
			if ($k !== "name") {
				$first_item++;
			}

			/*Cycle through array node if it has any children*/
			if ($k !== "name" && is_array($v)) {
				//Add current array pointer to the pointers list
				$keys[] = $k;
				/*Add current depth in array*/
				$current_depth++;
				/*recursive function Re call*/
				layout_extrusion($v, $map, $keys, $max_depth, $current_depth, $first_item);
				$current_depth--;
				array_pop($keys);
			}
		}
	}
}
/*
	Insert into multi-dimensional array by an array of keys
	arr: array source
	keys: array of keys
	value: value to insert
*/
function insert_using_keys($arr, $keys, $value)
{
	$a = &$arr;
	while (count($keys) > 0) {
		$k = array_shift($keys);
		if (!is_array($a)) {
			$a = array();
		}
		$a = &$a[$k];
	}
	$a = $value;
	return $arr;
}
/*
	Return a specific lines count
	sentence: input string
	lines: lines to return
	Returns array
*/
function get_lines($sentence, $lines = 3)
{
	$sentence = is_null($sentence) ? "" : $sentence;
	preg_match("/(?:[^\r\n]+(?:[\r\n]+|$)){0,$lines}/", $sentence, $matches);
	return array("original" => $sentence, "new" => rtrim($matches[0], "\r\n"), "identical" => $matches[0] != $sentence);
}
/*
	Limit GROUP BY array by only giver POST group list
*/
function Group_list_limit_active($source, $array)
{
	$temp = array();
	foreach ($source as $source_k => $source_v) {
		if ($array[$source_k]['active']) {
			$temp[$source_k] = $array[$source_k];
		}
	}
	return $temp;
}
/*
	Map an array IDs with corresponding names, setting excluding array IDs as `true` if presented `false` otherwise 
	sql: pointer
	_USER: pointer
	array: &reference - input IDs array (key: index, value: ID)
	array_exclusions: exclusion IDs array
	map: SQL table query (account, category_family, category)
	returns: NULL
	
*/
function id_maping(&$app, &$array, $array_exclusions, $map)
{
	$output = array();
	$serialized = "";
	$smart = "";
	$array_exclusions_index = array();
	$arr_map = array(
		"account" => "
			SELECT 
				prt_id AS _id,
				CONCAT (\"[\", cur_shortname , \"] \" , comp_name ,\": \" , ptp_name, \": \", prt_name) AS _name 
			FROM 
				`acc_accounts` 
					JOIN user_partition ON upr_prt_id=prt_id AND upr_usr_id='{$app->user->info->id}' AND upr_prt_view=1
					JOIN currencies ON cur_id=prt_currency
					JOIN `acc_accounttype` ON prt_type=ptp_id
					JOIN companies ON prt_company_id=comp_id
			WHERE 
				prt_id IN (",
		"category_family" => "SELECT accgrp_id AS _id,accgrp_name AS _name FROM acc_categorygroups WHERE accgrp_id IN (",
		"category" => "SELECT acccat_id AS _id,CONCAT(accgrp_name , ' : ', acccat_name) AS _name FROM acc_categories JOIN acc_categorygroups ON accgrp_id=acccat_group WHERE acccat_id IN (",

	);
	if (is_array($array) && sizeof($array) > 0) {
		foreach ($array as $k => $v) {
			if (isset($array_exclusions[$k]) && $array_exclusions[$k] == 'true') {
				$array_exclusions_index[$v] = true;
			} else {
				$array_exclusions_index[$v] = false;
			}
			$serialized .= $smart . ((int) $v);
			$smart = ",";
		}

		$r = $app->db->query("{$arr_map[$map]} $serialized) ORDER BY _name");
		while ($row = $r->fetch_assoc()) {
			$output[$row['_id']] = array("name" => $row['_name'], "excluded" => isset($array_exclusions_index[$row['_id']]) && $array_exclusions_index[$row['_id']] ? "1" : "0");
		}
	}
	$array = $output;
}

/*
Save custome user perpage setting
*/
if (isset($_POST['method']) && $_POST['method'] == 'save_per_page_setting') {
	$value = (int) $_POST['value'];
	$q = sprintf(
		"INSERT INTO user_settings (usrset_usr_id, usrset_type, usrset_usr_defind_name, usrset_value) VALUES (%1\$d," . \System\Personalization\Identifiers::AccountCustomePerpage->value . ",'UNIQUE',%2\$d) ON DUPLICATE KEY UPDATE usrset_value=%2\$d",
		$app->user->info->id,
		$value
	);
	$r = $app->db->query($q);
	echo $r ? "1" : "0";
	exit;
}

/*Load a saved SQL query*/
if (isset($_POST['method']) && $_POST['method'] == 'load_query') {
	$arr_output = array(
		"result" => false,
		"message" => "",
	);
	if (!isset($_POST['query_id'])) {
		$arr_output['result'] = false;
		$arr_output['message'] = "Query ID is missing";
		echo json_encode($arr_output);
		exit;
	}

	$r = $app->db->query("SELECT usrset_value FROM user_settings WHERE usrset_type = " . \System\Personalization\Identifiers::AccountCustomeQuerySave->value . " AND usrset_id={$_POST['query_id']} AND usrset_usr_id='{$app->user->info->id}'");
	if ($r) {
		if ($row = $r->fetch_assoc()) {
			$arr_output['result'] = true;
			$output = unserialize(base64_decode($row['usrset_value']));

			//check output
			$arr_schematic = array("creditor_account", "debitor_account", "category_family", "category");
			foreach ($arr_schematic as $sch_k => $sch_v) {
				if (!isset($output[$sch_v])) {
					$output[$sch_v] = array();
				}
			}

			id_maping($app, $output['creditor_account'], isset($output['creditor_account_exclude']) ? $output['creditor_account_exclude'] : array(), "account");
			id_maping($app, $output['debitor_account'], isset($output['debitor_account_exclude']) ? $output['debitor_account_exclude'] : array(), "account");
			id_maping($app, $output['category_family'], isset($output['category_family_exclude']) ? $output['category_family_exclude'] : array(), "category_family");
			id_maping($app, $output['category'], isset($output['category_exclude']) ? $output['category_exclude'] : array(), "category");

			unset($output['creditor_account_exclude'], $output['debitor_account_exclude'], $output['category_family_exclude'], $output['category_exclude'], $output['offset'], $output['method'], $output['group']);
			$arr_output['message'] = $output;
		}
	}
	echo json_encode($arr_output);
	exit;
}

/*Save filter query as a SQL query*/
if (isset($_POST['save_query'])) {
	$arr_output = array(
		"result" => false,
		"message" => ""
	);
	if (!isset($_POST['save_name']) || trim($_POST['save_name']) == "") {
		$arr_output['result'] = false;
		$arr_output['message'] = "Query title is required";
		echo json_encode($arr_output);
		exit;
	}
	$prepare = $_POST['save_query'];

	$_POST['save_name'] = str_replace(array("'", '"', "\\", "(", ")"), "-", $_POST['save_name']);
	$r = $app->db->query('INSERT INTO 
		user_settings (usrset_usr_id,usrset_type,usrset_usr_defind_name,usrset_value,usrset_time) VALUES (' . $app->user->info->id . ',' . \System\Personalization\Identifiers::AccountCustomeQuerySave->value . ',\'' . $_POST["save_name"] . '\',\'' .
		$prepare . '\',FROM_UNIXTIME(' . time() . ')) ON DUPLICATE KEY UPDATE
		usrset_value=\'' . $prepare . '\',
		usrset_time=FROM_UNIXTIME(\'' . time() . '\')
	');


	if ($r) {
		$arr_output['result'] = true;
		$arr_output['message'] = "Query save successfully";
		echo json_encode($arr_output);
	} else {
		$arr_output['result'] = false;
		$arr_output['message'] = "Query saving failed";
		echo json_encode($arr_output);
	}
	exit;
}

/*Delete a SQL query by ID*/
if (isset($_POST['method']) && $_POST['method'] == "delete_query") {
	if (!isset($_POST['query_id'])) {
		echo "0";
	}
	$r = $app->db->query("DELETE FROM user_settings WHERE usrset_usr_id={$app->user->info->id} AND usrset_type = " . \System\Personalization\Identifiers::AccountCustomeQuerySave->value . " AND usrset_id=" . ((int) $_POST['query_id']) . "");
	if ($r) {
		echo "1";
	} else {
		echo "0";
	}
	exit;
}

/*Filter query v2.16.1231.1300*/
if (isset($_POST['method']) && $_POST['method'] == 'filter') {
	$executingtime = microtime(true);
	include("99.prepare.php");
	/*Prefetch filter (count, sum)*/
	$fetch_report =
		"SELECT
			COUNT(DISTINCT(acm_id)) AS zcount,SUM(IF(acm_rejected=1,0,_accounts.atm_value)) AS zsum
		FROM
			acc_main
				JOIN 
					(
						SELECT
							atm_main,atm_account_id,_rates._rate * atm_value AS atm_value
						FROM
							`acc_accounts` 
								JOIN acc_temp ON prt_id=atm_account_id
								JOIN user_partition ON upr_prt_id=atm_account_id AND upr_usr_id={$app->user->info->id} AND upr_prt_view=1
								JOIN currencies ON cur_id = prt_currency
								JOIN companies ON comp_id = prt_company_id AND comp_id = {$app->user->company->id}
								
								LEFT JOIN (
									SELECT _from.curexg_from AS _rate_from,_to.curexg_from AS _rate_to,(_from.curexg_value / _to.curexg_value) AS _rate 
										FROM currency_exchange AS _from INNER JOIN currency_exchange AS _to
								) AS _rates ON _rates._rate_from = prt_currency AND _rates._rate_to = {$arr_listfixed['filtercurrency']}
								
							WHERE
								1 
								" . ($arr_listobjects['creditor_account']['active'] ? " AND ( atm_value < 0 AND (" . $arr_listobjects['creditor_account']['fields']['atm_account_id'] . ") ) " : "") . "
								" . ($arr_listobjects['debitor_account']['active'] ? ($arr_listobjects['creditor_account']['active'] ? " OR " : " AND ") . " ( atm_value > 0 AND (" . $arr_listobjects['debitor_account']['fields']['atm_account_id'] . ") )" : "") . "

					) AS _accounts ON _accounts.atm_main=acm_id
				
				LEFT JOIN 
					(SELECT atm_main,atm_account_id FROM acc_temp JOIN user_partition ON upr_prt_id=atm_account_id AND upr_usr_id={$app->user->info->id} AND upr_prt_view=1 WHERE atm_dir=0) 
						AS _credit ON _credit.atm_main = acm_id 
				LEFT JOIN 
					(SELECT atm_main,atm_account_id FROM acc_temp JOIN user_partition ON upr_prt_id=atm_account_id AND upr_usr_id={$app->user->info->id} AND upr_prt_view=1 WHERE atm_dir=1) 
						AS _debit ON _debit.atm_main = acm_id 
						
				LEFT JOIN
					(SELECT acccat_name,accgrp_name,acccat_id,accgrp_id FROM acc_categories JOIN acc_categorygroups ON accgrp_id=acccat_group) 
						AS _category ON _category.acccat_id=acm_category
		WHERE
			1
			AND NOT (_credit.atm_main IS  NULL AND _debit.atm_main IS  NULL)
			"
		. $active_combinde
		. ($arr_listobjects['creditor_account']['active_exclude'] ? " AND (NOT (" . $arr_listobjects['creditor_account']['exclude']['_credit.atm_account_id'] . ") OR _credit.atm_account_id IS NULL) " : "")
		. ($arr_listobjects['debitor_account']['active_exclude'] ? " AND (NOT (" . $arr_listobjects['debitor_account']['exclude']['_debit.atm_account_id'] . ") OR _debit.atm_account_id IS NULL) " : "")
		. ($arr_listfixed['display_altered'] ? " " : " AND acm_rejected!=1 ")
		. ($arr_listobjects['category_family']['active'] ? " AND ({$arr_listobjects['category_family']['fields']['accgrp_id']}) " : "")
		. ($arr_listobjects['category']['active'] ? " AND ({$arr_listobjects['category']['fields']['acm_category']}) " : "")
		. ($arr_listobjects['category_family']['active_exclude'] ? " AND NOT ({$arr_listobjects['category_family']['exclude']['accgrp_id']}) " : "")
		. ($arr_listobjects['category']['active_exclude'] ? " AND NOT ({$arr_listobjects['category']['exclude']['acm_category']}) " : "")
		. ($arr_listfixed['fromdate'] != null ? " AND acm_ctime>='{$arr_listfixed['fromdate']}' " : "")
		. ($arr_listfixed['todate'] != null ? " AND acm_ctime<='{$arr_listfixed['todate']}' " : "")
		. ($arr_listfixed['type'] != null ? " AND acm_type={$arr_listfixed['type']} " : "")
		. ($arr_listfixed['id'] != null ? " AND acm_id={$arr_listfixed['id']} " : "")
		. ($arr_listfixed['employee'] != null ? " AND acm_usr_id={$arr_listfixed['employee']} " : "")
		. ($arr_listfixed['editor'] != null ? " AND acm_editor_id={$arr_listfixed['editor']} " : "")
		. ($arr_listfixed['benifical'] != null ? " AND acm_beneficial RLIKE '.*" . replaceARABIC($arr_listfixed['benifical']) . ".*' " : "")
		. ($arr_listfixed['reference'] != null ? " AND acm_reference RLIKE '.*" . replaceARABIC($arr_listfixed['reference']) . ".*' " : "")
		. ($arr_listfixed['month-reference'] != null ? " AND (YEAR(acm_month)=YEAR('{$arr_listfixed['month-reference']}') AND MONTH(acm_month)=MONTH('{$arr_listfixed['month-reference']}') ) " : "")
		. ";";

	if ($r = $app->db->query($fetch_report)) {
		while ($row = $r->fetch_assoc()) {
			$arr_overview['total'] = $row['zcount'];
			$arr_overview['sum'] = $row['zsum'];
		}
	}
	if ($offset > (ceil($arr_overview['total'] / $per_page))) {
		$offset = (int) (ceil($arr_overview['total'] / $per_page)) - 1;
	}
	if ($ajax_debug && $debug_level == 0) {
		//echo $fetch_report;
		echo $fetch_report;
		echo "X>>" . $app->db->error;
		print_r($arr_overview);
		exit;
	}


	/*Output: Individual statements*/
	if ($start_grouping == false) {
		$query =
			"SELECT
				acm_id, acm_ctime,acm_beneficial,acm_comments,acm_type,acm_rejected,acm_usr_id,acm_reference,acm_month,acm_rel,
				acccat_name,accgrp_name,
				CONCAT_WS(' ',COALESCE(_editor.usr_firstname,''),IF(NULLIF(_editor.usr_lastname, '') IS NULL, NULL, _editor.usr_lastname)) AS _editor_name
			FROM
				acc_main
					LEFT JOIN
						(
							SELECT 
								acccat_name,accgrp_name,acccat_id,accgrp_id 
							FROM 
								acc_categories JOIN acc_categorygroups ON accgrp_id=acccat_group
						) AS _category ON _category.acccat_id=acm_category
					
					LEFT JOIN
						users AS _editor ON usr_id=acm_editor_id 
						
					LEFT JOIN 
						(
							SELECT 
								atm_main,atm_account_id 
							FROM 
								acc_temp 
									JOIN user_partition ON upr_prt_id=atm_account_id AND upr_usr_id={$app->user->info->id} AND upr_prt_view=1
									JOIN `acc_accounts` ON prt_id = atm_account_id AND prt_company_id = {$app->user->company->id}
							WHERE 
								atm_dir=0
						) AS _credit ON _credit.atm_main = acm_id 
					
					LEFT JOIN 
						(
							SELECT 
								atm_main,atm_account_id 
							FROM 
								acc_temp 
									JOIN user_partition ON upr_prt_id=atm_account_id AND upr_usr_id={$app->user->info->id} AND upr_prt_view=1
									JOIN `acc_accounts` ON prt_id = atm_account_id AND prt_company_id = {$app->user->company->id}
							WHERE atm_dir=1
						) AS _debit ON _debit.atm_main = acm_id 
					
			WHERE
				1
				AND NOT (_credit.atm_main IS  NULL AND _debit.atm_main IS  NULL)
				"
			. $active_combinde
			. ($arr_listobjects['creditor_account']['active_exclude'] ? " AND (NOT (" . $arr_listobjects['creditor_account']['exclude']['_credit.atm_account_id'] . ") OR _credit.atm_account_id IS NULL) " : "")
			. ($arr_listobjects['debitor_account']['active_exclude'] ? " AND (NOT (" . $arr_listobjects['debitor_account']['exclude']['_debit.atm_account_id'] . ") OR _debit.atm_account_id IS NULL) " : "")
			. ($arr_listfixed['display_altered'] ? " " : " AND acm_rejected!=1 ")
			. ($arr_listobjects['category_family']['active'] ? " AND ({$arr_listobjects['category_family']['fields']['accgrp_id']}) " : "")
			. ($arr_listobjects['category']['active'] ? " AND ({$arr_listobjects['category']['fields']['acm_category']}) " : "")
			. ($arr_listobjects['category_family']['active_exclude'] ? " AND NOT ({$arr_listobjects['category_family']['exclude']['accgrp_id']}) " : "")
			. ($arr_listobjects['category']['active_exclude'] ? " AND NOT ({$arr_listobjects['category']['exclude']['acm_category']}) " : "")
			. ($arr_listfixed['fromdate'] != null ? " AND acm_ctime>='{$arr_listfixed['fromdate']}' " : "")
			. ($arr_listfixed['todate'] != null ? " AND acm_ctime<='{$arr_listfixed['todate']}' " : "")
			. ($arr_listfixed['type'] != null ? " AND acm_type={$arr_listfixed['type']} " : "")
			. ($arr_listfixed['id'] != null ? " AND acm_id={$arr_listfixed['id']} " : "")
			. ($arr_listfixed['employee'] != null ? " AND acm_usr_id={$arr_listfixed['employee']} " : "")
			. ($arr_listfixed['editor'] != null ? " AND acm_editor_id={$arr_listfixed['editor']} " : "")
			. ($arr_listfixed['display_altered'] ? " " : " AND acm_rejected!=1 ")
			. ($arr_listfixed['benifical'] != null ? " AND acm_beneficial RLIKE '.*" . replaceARABIC($arr_listfixed['benifical']) . ".*' " : "")

			. ($arr_listfixed['reference'] != null ? " AND acm_reference RLIKE '.*" . replaceARABIC($arr_listfixed['reference']) . ".*' " : "")
			. ($arr_listfixed['month-reference'] != null ? " AND (YEAR(acm_month)=YEAR('{$arr_listfixed['month-reference']}') AND MONTH(acm_month)=MONTH('{$arr_listfixed['month-reference']}') ) " : "")
			. "
			ORDER BY 
				acm_ctime DESC,acm_id DESC
			LIMIT
				" . ($offset * $per_page) . ",$per_page;";

		$ftime = microtime(true);
		$r = $app->db->query($query);


		if (!($ajax_debug)) {
			echo "<div><div id=\"___ajax_sum\">{";
			echo "\"total\":{$arr_overview['total']},";
			echo "\"value\":\"" . number_format($arr_overview['sum'] ?? 0, 2, ".", ",") . "\",";
			echo "\"raw_value\":\"" . ($arr_overview['sum'] ?? 0) . "\",";
			echo "\"excution_time\":\"" . (microtime(true) - $ftime) . "\",";
			echo "\"offset\":" . $offset . ",";
			echo "\"pages\":" . (ceil($arr_overview['total'] / $per_page)) . ",";
			echo "\"operation\":\"normal\",";
			echo "\"debug\":0";

			echo "}</div><div id=\"___ajax_tbody\">";
		}

		if ($ajax_debug && $debug_level == 1) {
			//echo $query;
			echo "Y>>" . $query;
			exit;
		}

		if ($r) {
			$array_output = array();

			echo "<table class=\"bom-table hover screenCols\"><thead>
				<tr>
					<td>ID \ Issuer</td>
					<td align=\"right\">Value</td>
					<td>Creditor \ Debitor</td>
					<td>Beneficial</td>
					<td width=\"50%\" colspan=\"2\">Statement</td>
					
					" . ($fs()->permission->edit ? "<td style=\"width:10px\"></td>" : "") . "
					<td style=\"width:10px\"></td>
					<td style=\"width:10px\"></td>
				</tr>
			</thead>
			<tbody>";

			while ($row = $r->fetch_assoc()) {
				$array_output[$row['acm_id']] = array("info" => array(), "details" => array());
				$array_output[$row['acm_id']]["info"]["id"] = $row['acm_id'];
				$array_output[$row['acm_id']]["info"]["rejected"] = $row['acm_rejected'];
				$array_output[$row['acm_id']]["info"]["transaction_type"] = $row['acm_type'];
				$array_output[$row['acm_id']]["info"]["type"] = $row['acm_type'];
				$array_output[$row['acm_id']]["info"]["id"] = $row['acm_id'];
				$array_output[$row['acm_id']]["info"]["date"] = $row['acm_ctime'];
				$array_output[$row['acm_id']]["info"]["month"] = $row['acm_month'];
				$array_output[$row['acm_id']]["info"]["beneficial"] = $row['acm_beneficial'];
				$array_output[$row['acm_id']]["info"]["usr_id"] = $row['acm_usr_id'];
				$array_output[$row['acm_id']]["info"]["reference"] = $row['acm_reference'];
				$array_output[$row['acm_id']]["info"]["category_group"] = $row['accgrp_name'];
				$array_output[$row['acm_id']]["info"]["category_name"] = $row['acccat_name'];
				$array_output[$row['acm_id']]["info"]["editor"] = $row['_editor_name'];
				$array_output[$row['acm_id']]["info"]["comments"] = $row['acm_comments'];
				$array_output[$row['acm_id']]["info"]["rel"] = $row['acm_rel'];


				$sub_q = $app->db->query("
					SELECT 
						atm_value,atm_main,prt_name,cur_shortname,atm_dir,
						comp_name,ptp_name
					FROM
						`acc_accounts` 
							JOIN acc_temp ON prt_id=atm_account_id
							JOIN currencies ON cur_id = prt_currency 
							JOIN companies ON comp_id = prt_company_id 
							JOIN `acc_accounttype` ON ptp_id=prt_type
					WHERE atm_main={$row['acm_id']};");

				if ($sub_q) {
					while ($row_q = $sub_q->fetch_assoc()) {
						if ($row_q['atm_dir'] == 0) {
							//creditor
							$array_output[$row['acm_id']]["details"]['creditor'] = array();
							$array_output[$row['acm_id']]["details"]['creditor']['raw_value'] = number_format(abs($row_q['atm_value']), 2, ".", ",");
							$array_output[$row['acm_id']]["details"]['creditor']['value'] = ($row_q['atm_value'] < 0 ? "(" . number_format(abs($row_q['atm_value']), 2, ".", ",") . ")" : number_format($row_q['atm_value'], 2, ".", ","));
							$array_output[$row['acm_id']]["details"]['creditor']['account'] = $row_q['comp_name'] . ": " . $row_q['ptp_name'] . ": " . $row_q['prt_name'];
							$array_output[$row['acm_id']]["details"]['creditor']['currency'] = $row_q['cur_shortname'];
						} elseif ($row_q['atm_dir'] == 1) {
							//debitor
							$array_output[$row['acm_id']]["details"]['debitor'] = array();
							$array_output[$row['acm_id']]["details"]['debitor']['value'] = ($row_q['atm_value'] < 0 ? "(" . number_format(abs($row_q['atm_value']), 2, ".", ",") . ")" : number_format($row_q['atm_value'], 2, ".", ","));
							$array_output[$row['acm_id']]["details"]['debitor']['account'] = $row_q['comp_name'] . ": " . $row_q['ptp_name'] . ": " . $row_q['prt_name'];
							$array_output[$row['acm_id']]["details"]['debitor']['currency'] = $row_q['cur_shortname'];
						}
					}
					$sub_q->free_result();
				}
			}

			foreach ($array_output as $main) {
				if (isset($main['details']['creditor']['currency'], $main['details']['debitor']['currency'])) {
					echo "<tr" . ($main['info']['rejected'] == 1 ? " class=\"tran-deleted\"" : "") . ">";
					echo "<td>" . $main['info']['id'] . (isset($main['info']['rel']) && !is_null($main['info']['rel']) ? " @<a href=\"\">{$main['info']['rel']}</a>" : "") . "<br />{$main['info']['date']}<br />{$main['info']['editor']}</td>";




					if ($main['details']['creditor']['currency'] == $main['details']['debitor']['currency']) {
					} else {
					}

					echo "<td align=\"right\">
						<div class=\"payment_type crd\">{$main['details']['creditor']['value']}</div>
						<div class=\"payment_type dbt\">{$main['details']['debitor']['value']}</div>
						</td>";

					echo "<td class=\"payment_type\">
						<div class=\"payment_type crd\">[{$main['details']['creditor']['currency']}] {$main['details']['creditor']['account']}</div>
						<div class=\"payment_type dbt\">[{$main['details']['debitor']['currency']}] {$main['details']['debitor']['account']}</div>
						</td>";

					echo "<td>" . (is_null($main['info']['usr_id']) ? "" : "" . $main['info']['usr_id'] . ": ") . $main['info']['beneficial'] . "<br />" . $main['info']['category_group'] . ": " . $main['info']['category_name'] . "<br />" . $main['info']['reference'] . "</td>";

					$arr_comments = get_lines($main['info']['comments']);
					echo "<td class=\"detailed_comments\">" . ($arr_comments["identical"] ? "<span>" . nl2br($main['info']['comments']) . "</span>" : "") . nl2br($arr_comments["new"] . ($arr_comments["identical"] ? "..." : "")) . "</td>";


					$r_uploads = $app->db->query("
							SELECT up_id,up_name,up_size,up_date,up_user,up_mime
							FROM uploads JOIN pagefile_permissions ON pfp_trd_id=up_pagefile AND pfp_per_id = {$app->user->info->permissions}
							WHERE up_rel={$main['info']['id']} AND up_active=1 AND pfp_value>0 AND up_pagefile=" . TRANSACTION_ATTACHMENT_PAGEFILE . ";");
					$upload_list = array();
					while ($row_uploads = $r_uploads->fetch_assoc()) {
						$upload_list[] = $row_uploads;
					}

					echo "<td class=\"upload-list\">";
					if (!empty($upload_list)) {
						echo "<div data-acm_id=\"{$main['info']['id']}\"><span>";
						echo sizeof($upload_list);
						echo "</span><div>";
						foreach ($upload_list as $up_k => $up_v) {
							echo "<span data-mime=\"{$up_v['up_mime']}\">{$up_v['up_name']}</span>";
						}

						echo "</div></div>";
					}
					echo "</td>";

					echo $fs()->permission->edit ? "<td class=\"op-edit\"><a href=\"{$fs(101)->dir}/?id={$main['info']['id']}\"></a></td>" : "";
					echo "<td class=\"op-print\" data-id=\"{$main['info']['id']}\"><span></span></td>"; //xxxx
					echo "<td class=\"op-display\" ><a href=\"{$fs(104)->dir}/?id={$main['info']['id']}\"></a></td>";
					echo "</tr>";
				}
			}
			echo "</tbody></table>";
		}
	} else {
		/*Output: Group statements*/
		$q_group = "  ";
		$smart = "";
		foreach ($arr_group as $k => $v) {
			if ($v['active']) {
				$q_group .= $smart . $v['field'];
				$smart = ",";
			}
		}

		$group_query = "
			SELECT
				" . (isset($arr_group['year']) && $arr_group['year']['active'] ? " YEAR(acm_ctime) AS group_year, " : "") . "
				" . (isset($arr_group['month']) && $arr_group['month']['active'] ? " MONTH(acm_ctime) AS group_month, DATE_FORMAT(acm_ctime,'%M') AS group_month_name, " : "") . "
				" . (isset($arr_group['category_family']) && $arr_group['category_family']['active'] ? " accgrp_id,accgrp_name, " : "") . "
				" . (isset($arr_group['category']) && $arr_group['category']['active'] ? " acccat_id,acccat_name, " : "") . "
				" . (isset($arr_group['type']) && $arr_group['type']['active'] ? " acm_type, " : "") . "
				" . (isset($arr_group['account']) && $arr_group['account']['active'] ? " account_id, CONCAT (\"[\", cur_shortname , \"] \" , comp_name ,\": \" , ptp_name, \": \", account_name) AS account_name, " : "") . "
				" . (isset($arr_group['benifical']) && $arr_group['benifical']['active'] ? " acm_usr_id,CONCAT_WS(' ',COALESCE(usr_firstname,''),COALESCE(usr_lastname,''))  AS benifical, " : "") . "
				" . (isset($arr_group['benifical_t']) && $arr_group['benifical_t']['active'] ? " acm_beneficial, " : "") . "
				" . (isset($arr_group['reference']) && $arr_group['reference']['active'] ? " acm_reference, " : "") . "
				
				cur_symbol,SUM(atm_value) AS group_sum,COUNT(atm_value) AS group_count
			FROM
				acc_main
					LEFT JOIN users ON acm_usr_id=usr_id
					JOIN 
						(
						SELECT
							atm_main,prt_name AS account_name,prt_id AS account_id,
							( atm_value * _rates._rate ) AS atm_value,
							cur_symbol,cur_shortname,comp_name,ptp_name
						FROM
							`acc_accounts` 
								JOIN acc_temp ON prt_id=atm_account_id
								LEFT JOIN (
									SELECT _from.curexg_from AS _rate_from,_to.curexg_from AS _rate_to,(_from.curexg_value / _to.curexg_value) AS _rate 
										FROM currency_exchange AS _from INNER JOIN currency_exchange AS _to
								) AS _rates ON _rates._rate_from = prt_currency AND _rates._rate_to = {$arr_listfixed['filtercurrency']}
								JOIN user_partition ON upr_prt_id=atm_account_id AND upr_usr_id={$app->user->info->id} AND upr_prt_view=1
								JOIN currencies ON cur_id = prt_currency
								JOIN `acc_accounttype` ON prt_type=ptp_id
								JOIN companies ON comp_id = prt_company_id AND comp_id = {$app->user->company->id}
							WHERE
								1 
								" . ($arr_listobjects['creditor_account']['active'] ? " AND ( atm_value < 0 AND (" . $arr_listobjects['creditor_account']['fields']['atm_account_id'] . ") ) " : "") . "
								" . ($arr_listobjects['debitor_account']['active'] ? ($arr_listobjects['creditor_account']['active'] ? " OR " : " AND ") . " ( atm_value > 0 AND (" . $arr_listobjects['debitor_account']['fields']['atm_account_id'] . ") )" : "") . "
						) AS _accounts ON _accounts.atm_main=acm_id
						
					LEFT JOIN 
						(SELECT atm_main,atm_account_id FROM acc_temp JOIN user_partition ON upr_prt_id=atm_account_id AND upr_usr_id={$app->user->info->id} AND upr_prt_view=1 WHERE atm_dir=0) 
							AS _credit ON _credit.atm_main = acm_id 
					LEFT JOIN 
						(SELECT atm_main,atm_account_id FROM acc_temp JOIN user_partition ON upr_prt_id=atm_account_id AND upr_usr_id={$app->user->info->id} AND upr_prt_view=1 WHERE atm_dir=1) 
							AS _debit ON _debit.atm_main = acm_id 
					
					LEFT JOIN
						(SELECT
							acccat_name,accgrp_name,acccat_id,accgrp_id
						FROM
							acc_categories JOIN acc_categorygroups ON accgrp_id=acccat_group
						) AS _category ON _category.acccat_id=acm_category
			WHERE
				1 AND acm_rejected=0 AND NOT (_credit.atm_main IS  NULL AND _debit.atm_main IS NULL)"

			. $active_combinde
			. ($arr_listobjects['creditor_account']['active_exclude'] ? " AND (NOT (" . $arr_listobjects['creditor_account']['exclude']['_credit.atm_account_id'] . ") OR _credit.atm_account_id IS NULL) " : "")
			. ($arr_listobjects['debitor_account']['active_exclude'] ? " AND (NOT (" . $arr_listobjects['debitor_account']['exclude']['_debit.atm_account_id'] . ") OR _debit.atm_account_id IS NULL) " : "")

			. ($arr_listobjects['category_family']['active'] ? " AND ({$arr_listobjects['category_family']['fields']['accgrp_id']})" : "")
			. ($arr_listobjects['category']['active'] ? " AND ({$arr_listobjects['category']['fields']['acm_category']})" : "")
			. ($arr_listobjects['category_family']['active_exclude'] ? " AND NOT ({$arr_listobjects['category_family']['exclude']['accgrp_id']})" : "")
			. ($arr_listobjects['category']['active_exclude'] ? " AND NOT ({$arr_listobjects['category']['exclude']['acm_category']})" : "")
			. ($arr_listfixed['fromdate'] != null ? " AND acm_ctime>='{$arr_listfixed['fromdate']}'" : "")
			. ($arr_listfixed['todate'] != null ? " AND acm_ctime<='{$arr_listfixed['todate']}'" : "")
			. ($arr_listfixed['type'] != null ? " AND acm_type={$arr_listfixed['type']}" : "")
			. ($arr_listfixed['id'] != null ? " AND acm_id={$arr_listfixed['id']}" : "")
			. ($arr_listfixed['employee'] != null ? " AND acm_usr_id={$arr_listfixed['employee']}" : "")
			. ($arr_listfixed['editor'] != null ? " AND acm_editor_id={$arr_listfixed['editor']}" : "")
			. ($arr_listfixed['benifical'] != null ? " AND acm_beneficial RLIKE '.*" . replaceARABIC($arr_listfixed['benifical']) . ".*' " : "")
			. ($arr_listfixed['reference'] != null ? " AND acm_reference RLIKE '.*" . replaceARABIC($arr_listfixed['reference']) . ".*' " : "")
			. ($arr_listfixed['month-reference'] != null ? " AND (YEAR(acm_month)=YEAR('{$arr_listfixed['month-reference']}') AND MONTH(acm_month)=MONTH('{$arr_listfixed['month-reference']}') ) " : "")
			. "
			GROUP BY 
				$q_group
			ORDER BY 
				$q_group
			;";

		$r = $app->db->query($group_query);

		if (!($ajax_debug)) {
			echo "<div><div id=\"___ajax_sum\">{";
			echo "\"total\":{$arr_overview['total']},";
			echo "\"value\":\"" . number_format($arr_overview['sum'], 2, ".", ",") . "\",";
			echo "\"raw_value\":\"" . $arr_overview['sum'] . "\",";
			echo "\"excution_time\":\"" . (microtime(true)) . "\",";
			echo "\"offset\":0,";
			echo "\"pages\":1,";
			echo "\"operation\":\"group\",";
			echo "\"debug\":0";
			echo "}</div><div id=\"___ajax_tbody\">";
		}

		if ($ajax_debug && $debug_level == 3) {
			echo $group_query;
			exit;
		}

		if ($r) {
			$arr_output = array();
			$arr_output_raw = array();
			$max_depth = sizeof($arr_group);

			/*Build the output array (multi-dimensional) based on the group by array (single dimension)*/
			while ($row = $r->fetch_assoc()) {
				$arr_keys = array();
				$arr_keysofnames = array();
				foreach ($arr_group as $group_k => $group_v) {
					$arr_keys[] = $row[$group_v['field']];
					$name = array();
					if ($group_v['cols'] == "Type") {
						foreach ($group_v['reference'] as $ref_v) {
							$name[] = (\System\Finance\Transaction\Nature::tryFrom((int) $row[$ref_v])->toString());
						}
					} else {
						foreach ($group_v['reference'] as $ref_v) {
							$name[] = $row[$ref_v];
						}
					}
					$arr_output = insert_using_keys($arr_output, array_merge($arr_keys, ["name"]), $name);
				}
				$arr_output = insert_using_keys($arr_output, array_merge($arr_keys, array("val")), $row['group_sum']);
				$arr_output_raw = insert_using_keys($arr_output_raw, $arr_keys, $row['group_sum']);
			}
			$r->free_result();
			echo "<table class=\"bom-table group-list screenCols\"><thead><tr>";
			foreach ($arr_group as $group_k => $group_v) {
				echo "<td " . ($group_v['reference'] > 1 ? "colspan=\"" . sizeof($group_v['reference']) . "\"" : "") . ">" . $group_v['cols'] . "</td>";
			}
			echo "<td width=\"100%\">Values `" . $currency_list[$arr_listfixed['filtercurrency']]['symbol'] . "`</td>";
			echo "</tr></thead><tbody>";

			foreach ($arr_output as $output_k => $output_v) {
				layout_extrusion($output_v, $arr_output_raw, array($output_k), $max_depth, 0, 0);
			}

			echo "</tbody></table>";
		}
	}

	echo "</div></div>";
	exit;
}
if ($app->xhttp) {
	exit;
}


$SmartListObject = new SmartListObject($app);
$grem = new Gremium\Gremium();

$grem->header()->serve("<h1>Ledger Report</h1>");
unset($grem);

?><br />
<iframe style="display:none;" name="iframe" id="iframe"></iframe>
<form id="jQfilterform">
	<table class="bom-table" style="margin-bottom:10px">
		<tbody>
			<tr>
				<th style="width:25%">Creditor account</th>
				<th style="width:25%">Debitor account</th>
				<th style="width:25%">Category family</th>
				<th style="width:25%">Category type</th>
			</tr>
			<tr>
				<td valign="top" style="border:solid 1px var(--bomtable-border-color)">
					<div class="btn-set normal list"><input type="text" data-list_object="true" data-rel="creditor_account" data-slo="ACC_VIEW" /></div>
					<div class="slo_list" data-role="creditor_account">
						<?php
						echo $pre_load_variables ? "<span 
							data-id=\"{$app->user->account->id}\"><b>&#xea0f;</b><span>[{$app->user->account->currency->shortname}] {$app->user->company->name}: {$app->user->account->type->name}: {$app->user->account->name}</span><input type=\"hidden\" 
							name=\"creditor_account[1]\" value=\"{$app->user->account->id}\" /><label style=\"background-color:#fff\">Exclude<input name=\"creditor_account_exclude[1]\" type=\"checkbox\" ><span></span></label></span>" : "";
						?>
					</div>
				</td>
				<td valign="top" style="border:solid 1px var(--bomtable-border-color)">
					<div class="btn-set normal list"><input type="text" data-list_object="true" data-rel="debitor_account" data-slo="ACC_VIEW" /></div>
					<div class="slo_list" data-role="debitor_account">
						<?php
						echo $pre_load_variables ? "<span data-id=\"{$app->user->account->id}\"><b>&#xea0f;</b><span>[{$app->user->account->currency->shortname}] {$app->user->company->name}: {$app->user->account->type->name}: {$app->user->account->name}</span><input type=\"hidden\" name=\"debitor_account[1]\" value=\"{$app->user->account->id}\" /><label style=\"background-color:#fff\">Exclude<input name=\"debitor_account_exclude[1]\" type=\"checkbox\" ><span></span></label></span>" : "";
						?>
					</div>
				</td>

				<td valign="top" style="border:solid 1px var(--bomtable-border-color)">
					<div class="btn-set normal list"><input type="text" data-list_object="true" data-rel="category_family" data-slo="ACC_CATGRP" /></div>
					<div class="slo_list" data-role="category_family">
					</div>
				</td>
				<td valign="top" style="border:solid 1px var(--bomtable-border-color)">
					<div class="btn-set normal list"><input type="text" data-list_object="true" data-rel="category" data-slo="ACC_CAT" /></div>
					<div class="slo_list" data-role="category">
					</div>
				</td>
			</tr>
		</tbody>
	</table>
	<table class="bom-table" style="margin-bottom:10px">
		<tbody>
			<tr>
				<th style="min-width:100px;">ID</th>
				<td style="width:25%;">
					<div class="btn-set normal list"><input type="text" id="jQid" name="id" value="" /></div>
				</td>
				<th style="min-width:100px;">Editor</th>
				<td style="width:25%;" valign="top">
					<div class="btn-set normal list"><input type="text" data-input_object="true" name="editor" data-slo="ACC_EDITORS" /></div>
				</td>
				<th style="min-width: 100px;border-left: solid 1px #ccc;" rowspan="4">Group By</th>
				<td style="width: 25%;padding: 0px;" valign="top" rowspan="4">
					<div id="jQgroupable_list">
						<div><label><input type="checkbox" name="group[account]" value="1"><span>Account</span></label>
						</div>
						<div><label><input type="checkbox" name="group[type]" value="1"><span>Type</span></label></div>
						<div>
							<label><input type="checkbox" name="group[year]" value="1"><span>Year</span></label>
							<div>
								<label><input type="checkbox" name="group[month]" value="1"><span>Month</span></label>
							</div>
						</div>
						<div>
							<label><input type="checkbox" name="group[category_family]" value="1"><span>Category
									Family</span></label>
							<div>
								<label><input type="checkbox" name="group[category]" value="1"><span>Category</span></label>
							</div>
						</div>
						<div><label><input type="checkbox" name="group[benifical]" value="1"><span>Benifical
									ID</span></label></div>
						<div><label><input type="checkbox" name="group[benifical_t]" value="1"><span>Benifical
									Name</span></label></div>
						<div><label><input type="checkbox" name="group[reference]" value="1"><span>Reference</span></label></div>
					</div>
				</td>
			</tr>
			<tr>
				<th>Type</th>
				<td>
					<div class="btn-set normal list">
						<input type="text" data-input_object="true" name="type" data-slo=":SELECT" data-list="js-statement-type" />
					</div>
					<datalist id="js-statement-type">
						<?= $SmartListObject->financialTransactionNature(); ?>
					</datalist>
				</td>
				<th>Reference</th>
				<td>
					<div class="btn-set normal list"><input type="text" data-input_object="true" name="reference" data-slo="ACC_REFERENCE" /></div>
				</td>
			</tr>
			<tr>
				<th>Benifical</th>
				<td>
					<div class="btn-set normal list"><input type="text" data-input_object="true" name="benifical" data-slo=":LIST" data-list="beneficialList" /></div>
					<datalist id="beneficialList">
						<?= $SmartListObject->financialBeneficiary(); ?>
					</datalist>
				</td>
				<th>Employee</th>
				<td>
					<div class="btn-set normal list"><input type="text" data-input_object="true" name="employee" data-slo="B00S" /></div>
				</td>
			</tr>
			<tr>
				<th>Date range</th>
				<td>
					<div class="btn-set normal list"><!--
				--><input type="text" name="fromdate" data-input_object="true" value="<?php echo $pre_load_variables ? date("Y-m-d", mktime(0, 0, 0, date("m"), 1, date("Y"))) : ""; ?>" <?php echo $pre_load_variables ? " data-slodefaultid=\"" . date("Y-m-d", mktime(0, 0, 0, date("m"), 1, date("Y"))) . "\"" : ""; ?> data-slo="DATE" /><!--
				--><input type="text" name="todate" data-input_object="true" data-slo="DATE" /></div>
				</td>
				<th>Month reference</th>
				<td>
					<div class="btn-set normal list"><input name="month-reference" data-input_object="true" data-slo="MONTH" type="text" /></div>
				</td>
			</tr>
		</tbody>
	</table>
	<table class="bom-table">
		<tbody>
			<tr>
				<td>
					<div class="btn-set" style="justify-content:center"><label class="btn-checkbox"><input type="checkbox" name="strict_filter" /> <span>&nbsp;Strict accounts
								filter&nbsp;</span></label><button id="jQfetch" type="submit">Submit</button><button id="jQclear" type="button">Clear</button><button id="jQsave"
							type="button">Save</button><button id="jQload" type="button">Load</button></div>
				</td>
			</tr>
		</tbody>
	</table>

	<div id="jQtracer">
		<div class="overlay"></div>
		<table style="margin:15px 0px 5px 0px" width="100%">
			<tbody>
				<tr>
					<td width="50%">
						<div class="btn-set">
							<span id="jQnavRec">0</span>
							<input type="text" readonly id="jQresult_value" style="text-align:right;width:130px" value="0.00" /><input type="text" id="jQcurrency" name="filtercurrency"
								data-slo="CURRENCY_SYMBOL" style="width:70px" <?php echo $__systemdefaultcurrency ? " value=\"{$__systemdefaultcurrency['shortname']}\"" : ""; ?> <?php echo $__systemdefaultcurrency ? " data-slodefaultid=\"{$__systemdefaultcurrency['id']}\"" : ""; ?> />
							<span id="jQbalance_status">N\A</span>
							<span class="gap"></span>
							<label class="btn-checkbox" style="white-space: nowrap;"><input type="checkbox" name="display_altered" /> <span>&nbsp;Show deleted&nbsp;</span></label>

							<b id="jQperpage" class="menu_screen">
								<div><span data-per="25">25</span><span data-per="50">50</span><span data-per="75">75</span><span data-per="100">100</span></div>
							</b>
							<input type="text" style="text-align:center;width:130px;" readonly id="jQperpage_btn" value="<?php echo $per_page; ?> Perpage" />

							<button disabled id="jQnavFirst" type="button">First</button>
							<button disabled id="jQnavPrev" type="button">Previous</button>
							<input type="hidden" name="offset" id="jQoffset" />
							<b id="jQpageination" class="menu_screen">
								<div></div>
							</b>
							<input type="text" style="text-align:center;width:80px;" readonly id="jQnavTitle" value="No results" />
							<button disabled id="jQnavNext" type="button">Next</button>
							<button id="jQexport" type="button">Export</button>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
		<div id="jQoutput"></div>
	</div>
</form>
<iframe id="jQiframe" name="jQiframe" style="display:none;"></iframe>
<script>
	$(document).ready(function (e) {
		var $form = $("#jQfilterform");

		$("#jQoutput").on('click', '.upload-list > div', function () {
			var acm_id = $(this).attr("data-acm_id");
			var $ajax = $.ajax({
				type: "POST",
				url: "<?= $fs(192)->dir ?>",
				data: {
					"statement_id": acm_id
				}
			}).done(function (output) {
				popup.show(output);
			}).fail(function (a, b, c) {
				messagesys.failure(c);
			});

		})

		var export_order = false;
		$("#jQgroupable_list").sortable({
			axis: "y",
			containment: "parent",
			distance: 5,
			tolerance: "pointer",
		});
		var indexing = 1000;
		var pageination_list_timer = null;
		var perpage_list_timer = null;
		//var slo_line=$("input[data-input_object]").slo();

		var slo_line_object = {
			'fromdate': $("input[name=fromdate]").slo(),
			'todate': $("input[name=todate]").slo(),
			'month-reference': $("input[name=month-reference]").slo(),
			'type': $("input[name=type]").slo(),
			'reference': $("input[name=reference]").slo(),
			'benifical': $("input[name=benifical]").slo(),
			'employee': $("input[name=employee]").slo(),
			'editor': $("input[name=editor]").slo(),
		}
		var slo_list = $("input[data-list_object]").slo({
			onselect: function (data) {
				indexing++;
				var rel = (data.object.attr("data-rel"));
				var list = $(".slo_list[data-role=" + rel + "]");

				if (list.find("span[data-id=" + data.hidden + "]").length == 0) {
					list.prepend('<span data-id="' + data.hidden + '"><b>&#xea0f;</b><span>' + data.value + '</span><input type="hidden" name="' + rel + '[' + indexing + ']" value="' + data.hidden + '" /><label>Exclude<input name="' + rel + '_exclude[' + indexing + ']" type="checkbox" ><span></span></label></span>');
					data.this.clear();
				} else {
					data.this.clear();
					messagesys.success("Already in list")
				}
			}
		});
		$("#jQnavTitle,#jQpageination").on('mouseenter', function () {
			if (pageination_list_timer != null) {
				clearTimeout(pageination_list_timer);
			}
			if ($("#jQpageination > div").children().length > 0)
				$("#jQpageination > div").css("display", "block");
		}).on('mouseleave', function () {
			pageination_list_timer = setTimeout(function () {
				$("#jQpageination > div").css("display", "none");
			}, 500);
		});


		$("#jQperpage,#jQperpage_btn").on('mouseenter', function () {
			if (perpage_list_timer != null) {
				clearTimeout(perpage_list_timer);
			}
			$("#jQperpage > div").css("display", "block");
		}).on('mouseleave', function () {
			perpage_list_timer = setTimeout(function () {
				$("#jQperpage > div").css("display", "none");
			}, 500);
		});

		$("#jQperpage > div > span").on('click', function () {
			var _per = $(this).attr("data-per");
			var $ajax = $.ajax({
				type: "POST",
				url: "<?php echo $fs()->dir; ?>",
				data: {
					"method": "save_per_page_setting",
					"value": _per
				}
			}).done(function (output) {
				$("#jQperpage_btn").val(_per + " Perpage");
				fetch();
			}).fail(function (a, b, c) {
				messagesys.failure(c);
			});

		});


		$("#jQsave").on('click', function () {

			var seria = $form.serialize({
				checkboxesAsBools: true
			});
			overlay.show();
			$.ajax({
				data: seria + "&method=filter",
				url: '<?= $fs(164)->dir ?>',
				type: 'POST'
			}).done(function (data) {
				overlay.hide();
				popup.show(data);
				var $save_form = popup.self().find("#jQsaveform");

				var $save_name = popup.self().find("#jQsave_name");
				$save_name.focus();
				popup.self().find("#jQsave_cancel").on('click', function () {
					popup.hide();
				});
				popup.self().find("#jQsave_submit").on('click', function () {
					$save_form.submit();

				});

				$save_form.on('submit', function (e) {
					e.preventDefault();
					if ($save_name.val().trim() == "") {
						messagesys.failure("Type in query name");
						return;
					}
					overlay.show();
					$.ajax({
						data: $save_form.serialize(),
						url: '<?php echo $fs()->dir; ?>',
						type: 'POST'
					}).done(function (data) {
						overlay.hide();
						var json = null;
						try {
							json = JSON.parse(data);
						} catch (e) {
							messagesys.failure("Parsing output failed");
							return false;
						}
						if (json.result) {
							messagesys.success(json.message);
							popup.hide();
						} else {
							messagesys.failure(json.message);
						}
					}).fail(function (a, b, c) {
						messagesys.failure(b);
						overlay.hide();
					});
					return false;
				});
			});
		});
		$("#jQload").on('click', function () {
			overlay.show();
			$.ajax({
				data: {
					"method": "load"
				},
				url: '<?= $fs(165)->dir ?>',
				type: 'POST'
			}).done(function (data) {
				overlay.hide();
				popup.show(data);
				popup.self().find("#jQload_cancel").focus();
				popup.self().find("#jQload_cancel").on('click', function () {
					popup.hide();
				});
				popup.self().find(".jQload_query").on('click', function () {
					var $tr = $(this).closest("tr");
					var _id = $tr.attr("data-id");
					overlay.show();
					$.ajax({
						data: {
							"method": "load_query",
							"query_id": _id
						},
						url: '<?php echo $fs()->dir; ?>',
						type: 'POST'
					}).done(function (data) {
						overlay.hide();
						var json = null;
						try {
							json = JSON.parse(data);
						} catch (e) {
							messagesys.failure("Parsing output failed");
							return false;
						}

						if (json.result) {
							slo_list.clear();
							for (var slo_object in slo_line_object) {
								slo_line_object[slo_object].clear();
							}
							slo_line_object['fromdate'].set(json.message.fromdate['1'], json.message.fromdate['0']);
							slo_line_object['todate'].set(json.message.todate['1'], json.message.todate['0']);
							slo_line_object['month-reference'].set(json.message['month-reference']['1'], json.message['month-reference']['0']);
							slo_line_object['type'].set(json.message['type']['1'], json.message['type']['0']);
							slo_line_object['reference'].set(json.message.reference['1'], json.message.reference['0']);
							slo_line_object['benifical'].set(json.message.benifical['1'], json.message.benifical['0']);
							slo_line_object['employee'].set(json.message.employee['1'], json.message.employee['0']);
							slo_line_object['editor'].set(json.message.editor['1'], json.message.editor['0']);

							$("#jQid").val(json.message.id);

							var lists = ['creditor_account', 'debitor_account', 'category_family', 'category'];
							for (var listitem in lists) {
								var list = $(".slo_list[data-role=" + lists[listitem] + "]");
								list.empty();
								var rel = lists[listitem];
								for (var groupitem in json.message[lists[listitem]]) {
									indexing++;
									list.prepend('<span data-id="' + groupitem + '"><b>&#xea0f;</b><span>' +
										json.message[lists[listitem]][groupitem]['name'] + '</span><input type="hidden" name="' + rel + '[' + indexing + ']" value="' +
										groupitem + '" /><label>Exclude<input name="' + rel + '_exclude[' + indexing + ']" ' + (~~json.message[lists[listitem]][groupitem]['excluded'] == 1 ? " checked=\"checked\"" : "") + ' type="checkbox" ><span></span></label></span>');
								}
							}
							fetch();
							popup.hide();
						} else {
							messagesys.failure("Parsing query failed");
						}
					}).fail(function (a, b, c) {
						overlay.hide();
					});
				});


				popup.self().find(".jQremove_query").on('click', function () {
					var $tr = $(this).closest("tr");
					var _id = $tr.attr("data-id");
					overlay.show();
					$.ajax({
						data: {
							"method": "delete_query",
							"query_id": _id
						},
						url: '<?php echo $fs()->dir; ?>',
						type: 'POST'
					}).done(function (data) {
						overlay.hide();
						if (data == "1") {
							messagesys.success("Query removed successfully");
							$tr.remove();
						} else {
							messagesys.success("Query removing failed");
						}
					}).fail(function () {
						overlay.hide();
					});
				});
			});
		});
		$(".menu_screen > div,.slo_list").on('mousewheel DOMMouseScroll', function (e) {
			var scrollTo = null;
			if (e.type == 'mousewheel') {
				scrollTo = (e.originalEvent.wheelDelta * -1);
			} else if (e.type == 'DOMMouseScroll') {
				scrollTo = 40 * e.originalEvent.detail;
			}
			if (scrollTo) {
				e.preventDefault();
				$(this).scrollTop(scrollTo + $(this).scrollTop());
			}
		});
		$(".slo_list").on('click', '> span > b', function () {
			var $this = $(this);
			$this.parent().remove();
		});

		var filtercurrency = $("#jQcurrency").slo({
			onselect: function (data) {
				fetch();
			},
			ondeselect: function (data) {
				fetch();
			}
		});
		var $ajax = null;

		var fetch = function () {
			if ($ajax != null) {
				$ajax.abort();
			}

			let formSerialized = $form.serialize();
			overlay.show();
			$ajax = $.ajax({
				url: "<?php echo $fs()->dir; ?>",
				type: "POST",
				data: formSerialized + "&method=filter",
			}).done(function (data) {
				<?php echo ($ajax_debug) ? "console.clear();console.log(data);return;" : ""; ?>

				var $data = $(data);
				var json = null;
				try {
					json = JSON.parse($data.find("#___ajax_sum").html());
				} catch (e) {
					messagesys.failure("Parsing output failed");
					return false;
				}
				$("#jQpageination > div").children().remove();
				$("#jQvalue").val(json.value);
				if (json.total > 0) {
					$("#jQnavTitle").val((json.offset + 1) + "/" + json.pages);
					if (json.offset == 0) {
						$("#jQnavPrev").prop("disabled", true);
					} else {
						$("#jQnavPrev").prop("disabled", false);
					}
					if (json.offset == 0) {
						$("#jQnavFirst").prop("disabled", true);
					} else {
						$("#jQnavFirst").prop("disabled", false);
					}
					if (json.offset + 1 == json.pages) {
						$("#jQnavNext").prop("disabled", true);
					} else {
						$("#jQnavNext").prop("disabled", false);
					}
					if (json.offset + 1 == json.pages) {
						$("#jQnavLast").prop("disabled", true);
					} else {
						$("#jQnavLast").prop("disabled", false);
					}
					if (json.operation == "normal") {
						$("#jQnavRec").html(json.total);
					} else if (json.operation == "group") {
						$("#jQnavRec").html("Group");
					}

					$("#jQoffset").val(json.offset);
					$("#jQresult_value").val(json.value);

					if (json.raw_value == 0) {
						$("#jQbalance_status").html("Balanced");
					} else if (json.raw_value > 0) {
						$("#jQbalance_status").html("Debit");
					} else if (json.raw_value < 0) {
						$("#jQbalance_status").html("Credit");
					} else {
						$("#jQbalance_status").html("N\\A");
					}
					if (json.pages > 1) {
						for (var listcount = 1; listcount <= json.pages; listcount++) {
							$("#jQpageination > div").append("<span data-pos=\"" + listcount + "\">" + listcount + "</span>");
						}
					}
					//$("#jQgap").html(json.excution_time);

				} else {
					$("#jQnavTitle").val("No results");
					$("#jQnavPrev").prop("disabled", true);
					$("#jQnavNext").prop("disabled", true);
					$("#jQnavRec").html("0 Records");
					$("#jQresult_value").val("0.00");
					$("#jQbalance_status").html("N\\A");
				}

				$("#jQoutput").html($data.find("#___ajax_tbody").html());
			}).fail(function (a, b, c) {
				messagesys.failure("Processing request failed `" + c + "`");
			}).always(function () {
				overlay.hide();
			});
		}

		$("#jQpageination > div").on('click', 'span', function () {
			var pos = $(this).attr("data-pos");
			$("#jQoffset").val(~~pos - 1);
			fetch();
		});

		$("#jQnavNext").on('click', function () {
			$("#jQoffset").val(~~$("#jQoffset").val() + 1);
			fetch();
		});
		$("#jQnavFirst").on('click', function () {
			$("#jQoffset").val(0);
			fetch();
		});

		$("#jQclear").on('click', function () {
			for (var slo_object in slo_line_object) {
				slo_line_object[slo_object].clear();
			}
			$("#jQoffset").val(0);
			$(".slo_list").html("");
			$("#jQid").val("");
			//$("#jQgroupable_list").find("input[type=checkbox]").prop("checked",false);
			fetch();
		});
		$("#jQnavPrev").on('click', function () {
			$("#jQoffset").val(~~$("#jQoffset").val() - 1);
			fetch();
		});

		$("#jQoutput").on('click', ".op-edit > a", function (e) {
			e.preventDefault();
			var $this = $(this);

			$("#jQtracer").find(" > div.overlay").css({
				'display': 'block'
			});
			var $ajax = $.ajax({
				type: "POST",
				url: $this.attr("href") + "&ajax",
				data: ""
			}).done(function (data) {
				popup.show(data);
			}).always(function () {
				$("#jQtracer").find(" > div.overlay").css({
					'display': 'none'
				});
			});
			return false;
		});
		$("#jQoutput").on('click', ".op-display > a", function (e) {
			e.preventDefault();
			var $this = $(this);
			$("#jQtracer").find(" > div.overlay").css({
				'display': 'block'
			});
			var $ajax = $.ajax({
				type: "POST",
				url: $this.attr("href") + "&ajax",
				data: ""
			}).done(function (data) {
				popup.show(data);
			}).always(function () {
				$("#jQtracer").find(" > div.overlay").css({
					'display': 'none'
				});
			});
			return false;
		});

		$("#jQoutput").on('click', ".op-print", function (e) {
			const id = $(this).attr('data-id');
			const objPrintFrame = window.frames['jQiframe'];
			objPrintFrame.location = "<?= $fs(142)->dir ?>/?id=" + id;
			document.getElementById("jQiframe").onload = function () {
				objPrintFrame.focus();
				objPrintFrame.print();
			}
		});


		$form.on('submit', function (e) {
			if (!export_order) {
				$form.attr("method", "");
				$form.attr("action", "");
				$form.removeAttr("target");
				e.preventDefault();
				fetch();
				return false;
			}
		});
		$("#jQexport").on('click', function () {
			export_order = true;
			$form.attr("method", "post");
			$form.attr("action", "<?= $fs(120)->dir ?>");
			$form.attr("target", "iframe");
			$form.submit();
			export_order = false;
		});
		fetch();
	});
</script>