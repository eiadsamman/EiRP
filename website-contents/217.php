<?php
$ident = \System\Personalization\Identifiers::SystemCountAccountSelection->value;
$r     = $app->db->query(
	"SELECT
		 SUM(atm_value) AS total_accountgroup, mua.upr_prt_fetch,mua.comp_id,
		 mua.comp_name, mua.cur_shortname, mua.prt_name, mua.prt_id
	FROM 
		acc_temp 
		JOIN acc_main ON acm_id = atm_main
		JOIN (
			SELECT 
				comp_name, comp_id, cur_shortname, prt_name,  prt_id,upr_prt_fetch, usrset_value
			FROM 
				view_financial_accounts
					JOIN user_partition ON prt_id = upr_prt_id AND upr_usr_id = {$app->user->info->id} AND upr_prt_view = 1
					LEFT JOIN user_settings ON usrset_usr_defind_name = prt_id AND usrset_usr_id = {$app->user->info->id} AND usrset_type = $ident
			ORDER BY 
				(usrset_value + 0) DESC, prt_name
			LIMIT 5
		) AS mua ON mua.prt_id = atm_account_id
	WHERE
		acm_rejected = 0
	GROUP BY
		mua.prt_id
	ORDER BY 
		mua.usrset_value DESC;"
);

if ($r) {
	echo "<div class=\"widgetWQU\"><div><div>";
	while ($row = $r->fetch_assoc()) {
		echo "<div><div class=\"btn-set\" style=\"flex-wrap: nowrap; \">";
		$account_title = "[{$row['cur_shortname']}] {$row['comp_name']}: {$row['prt_name']}"; 
		if ((int) $row['upr_prt_fetch'] == 1 && $row['comp_id'] == $app->user->company->id) {
			echo "<a class=\"flex\" href=\"?--sys_sel-change=account_commit&i={$row['prt_id']}\" title=\"\">$account_title</a>";
		} else {
			echo "<span class=\"nofetch flex\">$account_title</span>";
		}
		echo "<input type=\"text\" style=\"min-width:80px;text-align:right;\" readonly=\"readonly\" tabindex=\"-1\" value=\"" . ($row['total_accountgroup'] < 0 ? "(" . number_format(abs($row['total_accountgroup']), 2, ".", ",") . ")" : number_format(abs($row['total_accountgroup']), 2, ".", ",")) . "\" />";
		echo "</div></div>";
	}
	echo "</div></div></div>";
}