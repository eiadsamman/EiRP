<?php

use System\Controller\Finance\Accounting;

function replaceARABIC($str){
	$str=str_replace(["أ","إ","آ"],"[أإاآ]+",$str);
	$str=str_replace(["ة","ه"],"[ةه]+",$str);
	$str=str_replace(["ى","ي"],"[يى]+",$str);
	return $str;
}

if ($_SERVER['REQUEST_METHOD'] != "POST") {
	exit;
}
$output = fopen('php://output', 'w');
$debug = false;
$debug_level = "fatal";
$accounts_comparition_style = " OR ";
$accounting = new Accounting($app);
$__systemdefaultcurrency = $accounting->system_default_currency();

include("99.prepare.php");

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment;filename="' . $c__settings['site']['title'] . " " . date("Y-m-d") . '.csv"');
header('Cache-Control: max-age=1');
header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: cache, must-revalidate');
header('Pragma: public');

$arrheader = array("ID", "Type", "Value", "Currency", "Account", "Date", "Beneficial", "Category Group", "Category", "Editor", "Statement");

fputcsv($output, $arrheader);


$query = "
	SELECT
			acm_id,UNIX_TIMESTAMP(acm_ctime) AS acm_ctime,acm_beneficial,acm_comments,acm_type,acm_rejected,acm_usr_id,acm_reference,UNIX_TIMESTAMP(acm_month) AS acm_month,
			acccat_name,accgrp_name,acccat_id,accgrp_id,
			CONCAT_WS(' ',COALESCE(_editor.usr_firstname,''),IF(NULLIF(_editor.usr_lastname, '') IS NULL, NULL, _editor.usr_lastname)) AS _editor_name
		FROM
			acc_main
				LEFT JOIN
					(SELECT acccat_name,accgrp_name,acccat_id,accgrp_id FROM acc_categories JOIN acc_categorygroups ON accgrp_id=acccat_group) 
						AS _category ON _category.acccat_id=acm_category
				LEFT JOIN
					users AS _editor ON usr_id=acm_editor_id 
					
				LEFT JOIN 
					(SELECT atm_main,atm_account_id FROM acc_temp JOIN user_partition ON upr_prt_id=atm_account_id AND upr_usr_id={$app->user->info->id} WHERE atm_value < 0) 
						AS _credit ON _credit.atm_main = acm_id 
				LEFT JOIN 
					(SELECT atm_main,atm_account_id FROM acc_temp JOIN user_partition ON upr_prt_id=atm_account_id AND upr_usr_id={$app->user->info->id} WHERE atm_value > 0) 
						AS _debit ON _debit.atm_main = acm_id 
				
		WHERE
			1
			AND NOT (_credit.atm_main IS  NULL AND _debit.atm_main IS  NULL)
			"

	. $active_combinde

	. ($arr_listobjects['creditor_account']['active_exclude'] ? " AND (NOT (" . $arr_listobjects['creditor_account']['exclude']['_credit.atm_account_id'] . ") OR _credit.atm_account_id IS NULL) " : "")
	. ($arr_listobjects['debitor_account']['active_exclude'] ? " AND (NOT (" . $arr_listobjects['debitor_account']['exclude']['_debit.atm_account_id'] . ") OR _debit.atm_account_id IS NULL) " : "")


	. ($arr_listfixed['display_altered'] ? " AND acm_rejected!=1 " : " AND acm_rejected!=1 ")
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
		;";

$r = $app->db->query($query);
if ($r) {
	$array_output = array();
	while ($row = $r->fetch_assoc()) {
		$array_output[$row['acm_id']] = array("info" => array(), "details" => array());
		$array_output[$row['acm_id']]["info"]["id"] = $row['acm_id'];
		$array_output[$row['acm_id']]["info"]["rejected"] = $row['acm_rejected'];
		$array_output[$row['acm_id']]["info"]["transaction_type"] = $row['acm_type'];
		$array_output[$row['acm_id']]["info"]["type"] = $row['acm_type'];
		$array_output[$row['acm_id']]["info"]["id"] = $row['acm_id'];
		$array_output[$row['acm_id']]["info"]["date"] = date("Y-m-d", $row['acm_ctime']);
		$array_output[$row['acm_id']]["info"]["month"] = !is_null($row['acm_month']) && $row['acm_month'] != 0 ? date("Y-m", $row['acm_month']) : "";
		$array_output[$row['acm_id']]["info"]["beneficial"] = $row['acm_beneficial'];
		$array_output[$row['acm_id']]["info"]["reference"] = $row['acm_reference'];
		$array_output[$row['acm_id']]["info"]["category_group"] = $row['accgrp_name'];
		$array_output[$row['acm_id']]["info"]["category_name"] = $row['acccat_name'];
		$array_output[$row['acm_id']]["info"]["editor"] = $row['_editor_name'];
		$array_output[$row['acm_id']]["info"]["comments"] = $row['acm_comments'];


		$sub_q = $app->db->query("
				SELECT atm_value,atm_main,prt_name,cur_shortname,atm_dir
				FROM
					`acc_accounts` 
						JOIN acc_temp ON prt_id=atm_account_id
						LEFT JOIN currencies ON cur_id = prt_currency
				WHERE atm_main={$row['acm_id']};");

		if ($sub_q) {
			while ($row_q = $sub_q->fetch_assoc()) {
				if ($row_q['atm_dir'] == 0) {
					//creditor
					$array_output[$row['acm_id']]["details"]['creditor'] = array();
					$array_output[$row['acm_id']]["details"]['creditor']['raw_value'] = number_format($row_q['atm_value'], 4, ".", "");
					$array_output[$row['acm_id']]["details"]['creditor']['value'] = ($row_q['atm_value'] < 0 ? "(" . number_format(abs($row_q['atm_value']), 2, ".", ",") . ")" : number_format($row_q['atm_value'], 2, ".", ","));
					$array_output[$row['acm_id']]["details"]['creditor']['account'] = $row_q['prt_name'];
					$array_output[$row['acm_id']]["details"]['creditor']['currency'] = $row_q['cur_shortname'];
				} elseif ($row_q['atm_dir'] == 1) {
					//debitor
					$array_output[$row['acm_id']]["details"]['debitor'] = array();
					$array_output[$row['acm_id']]["details"]['debitor']['raw_value'] = number_format($row_q['atm_value'], 4, ".", "");
					$array_output[$row['acm_id']]["details"]['debitor']['value'] = ($row_q['atm_value'] < 0 ? "(" . number_format(abs($row_q['atm_value']), 2, ".", ",") . ")" : number_format($row_q['atm_value'], 2, ".", ","));
					$array_output[$row['acm_id']]["details"]['debitor']['account'] = $row_q['prt_name'];
					$array_output[$row['acm_id']]["details"]['debitor']['currency'] = $row_q['cur_shortname'];
				}
			}
		}

		$sub_q->free_result();
		foreach ($array_output as $main) {
			fputcsv($output, array(
				$main['info']['id'],
				\System\Controller\Finance\Transaction\enums\Type::tryFrom((int)$main['info']['transaction_type'])->value,
				$main['details']['creditor']['raw_value'],
				$main['details']['creditor']['currency'],
				$main['details']['creditor']['account'],
				$main['info']['date'],
				$main['info']['beneficial'],
				$main['info']['category_group'],
				$main['info']['category_name'],
				$main['info']['editor'],
				$main['info']['comments']
			));

			fputcsv($output, array(
				$main['info']['id'],
				\System\Controller\Finance\Transaction\enums\Type::tryFrom((int)$main['info']['transaction_type'])->value,
				$main['details']['debitor']['raw_value'],
				$main['details']['debitor']['currency'],
				$main['details']['debitor']['account'],
				$main['info']['date'],
				$main['info']['beneficial'],
				$main['info']['category_group'],
				$main['info']['category_name'],
				$main['info']['editor'],
				$main['info']['comments']
			));
		}
	}
}
