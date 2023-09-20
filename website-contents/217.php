<?php

$r = $app->db->query(
	"SELECT
		 SUM(atm_value) AS total_accountgroup, mua.upr_prt_fetch,
		 mua.comp_name, mua.cur_shortname, mua.ptp_name, mua.prt_name, mua.prt_id
	FROM 
		acc_temp 
		JOIN acc_main ON acm_id = atm_main
		JOIN (
			SELECT 
				comp_name, cur_shortname, ptp_name, prt_name,  prt_id,upr_prt_fetch
			FROM 
				view_financial_accounts
					
					JOIN user_partition ON prt_id = upr_prt_id AND upr_usr_id = {$app->user->info->id} AND upr_prt_view = 1
					LEFT JOIN user_settings ON usrset_usr_defind_name = prt_id AND usrset_usr_id = {$app->user->info->id} AND usrset_type = " . \System\Personalization\Identifiers::SystemCountAccountSelection->value . "
			ORDER BY 
				(usrset_value + 0) DESC
			LIMIT 5
		) AS mua ON mua.prt_id = atm_account_id
	
	WHERE
		acm_rejected = 0
	GROUP BY
		mua.prt_id
	ORDER BY 
		mua.comp_name ASC, mua.ptp_name ASC, mua.prt_name ASC
	LIMIT 5;"
);

if ($r) {
	echo "<div class=\"widgetWQU\"><div><div>";
	while ($row = $r->fetch_assoc()) {
		echo "<div><div class=\"btn-set\" style=\"flex-wrap: nowrap; \">";
		if ((int) $row['upr_prt_fetch'] == 1) {
			echo "<a class=\"flex\" href=\"?--sys_sel-change=account_commit&i={$row['prt_id']}\" title=\"{$row['comp_name']}: {$row['ptp_name']}: {$row['prt_name']}\">{$row['comp_name']}: {$row['ptp_name']}: {$row['prt_name']}</a>";
		} else {
			echo "<span class=\"nofetch flex\" title=\"{$row['comp_name']}: {$row['ptp_name']}: {$row['prt_name']}\">{$row['comp_name']}: {$row['ptp_name']}: {$row['prt_name']}</span>";
		}
		echo "<input type=\"text\" style=\"min-width:120px;text-align:right;\" readonly=\"readonly\" tabindex=\"-1\" value=\"" . ($row['total_accountgroup'] < 0 ? "(" . number_format(abs($row['total_accountgroup']), 2, ".", ",") . ")" : number_format(abs($row['total_accountgroup']), 2, ".", ",")) . "\" />";
		echo "<span style=\"width:60px;text-align:center;\">" . $row['cur_shortname'] . "</span>";
		echo "</div></div>";
	}
	echo "</div></div></div>";
}