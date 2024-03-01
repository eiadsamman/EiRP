<?php
use System\Graphics\SVG\CurveRelative;

if (!$app->xhttp && $app->user->company->id) {
	echo "<div class=\"accounts-overview\"><div class=\"tickets\" data-uri=\"{$fs(217)->dir}\">";
	echo "</div></div>";
} else {
	$curve = new CurveRelative(12, 10, -.5);
	$accounts_count = 4;

	$ident = \System\Personalization\Identifiers::SystemCountAccountSelection->value;

	$date_object = new \DateTime();
	$date_object->modify("-6 day");
	$date_interval = new DateInterval("P1D");

	$date_range = 7;
	$date_map = array();
	$date_stamp_start = $date_object->format("Y-m-d");
	$date_map[$date_stamp_start] = 0;

	for ($i = 1; $i < $date_range; $i++) {
		$date_object->add($date_interval);
		$date_map[$date_object->format("Y-m-d")] = 0;
	}
	$date_stamp_end = $date_object->format("Y-m-d");

	$r = (
		"SELECT
		mua.prt_id,
		acm_ctime,
		SUM(atm_value) AS sum_grp
	FROM 
		acc_temp 
		JOIN acc_main ON acm_id = atm_main
		JOIN (
			SELECT 
				prt_id
			FROM 
				acc_accounts
					JOIN user_partition ON prt_id = upr_prt_id AND upr_usr_id = {$app->user->info->id} AND upr_prt_view = 1
					LEFT JOIN user_settings ON usrset_usr_defind_name = prt_id AND usrset_usr_id = {$app->user->info->id} AND usrset_type = $ident
			ORDER BY 
				(usrset_value + 0) DESC, prt_name
			LIMIT $accounts_count
		) AS mua ON mua.prt_id = atm_account_id
	WHERE
		acm_rejected = 0 AND acm_ctime >= \"$date_stamp_start\" AND acm_ctime <= \"$date_stamp_end\"
	GROUP BY
		mua.prt_id, acm_ctime
	ORDER BY 
		mua.prt_id, acm_ctime ASC;"
	);

	$r = $app->db->query($r);

	$report = array();
	while ($row = $r->fetch_assoc()) {
		if (!isset($report[$row['prt_id']])) {
			$report[$row['prt_id']] = $date_map;
		}
		$report[$row['prt_id']][$row['acm_ctime']] = (float) $row['sum_grp'];
	}

	foreach ($report as $k => &$v) {
		$curve->prepareArray($v);
	}


	$r = $app->db->query(
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
			LIMIT $accounts_count
		) AS mua ON mua.prt_id = atm_account_id
	WHERE
		acm_rejected = 0
	GROUP BY
		mua.prt_id
	ORDER BY 
		mua.usrset_value DESC
	;"
	);


	if ($r) {
		$count = 0;
		while ($row = $r->fetch_assoc()) {
			$count++;
			$title = "{$row['comp_name']}, {$row['prt_name']}: " . number_format(abs($row['total_accountgroup']), 0, ".", ",") . " {$row['cur_shortname']}";
			echo "<span title=\"$title\">";// draggable=\"true\"
			echo "<h1><div>{$row['comp_name']}</div></h1>";
			echo "<h2>{$row['prt_name']}</h2>";
			if ($row['total_accountgroup'] >= 0) {
				echo "<span style=\"color:var(--root-font-color);\">" . number_format(abs($row['total_accountgroup']), 0, ".", ",") . "</span>";
			} else {
				echo "<span style=\"color:var(--root-font-color);\">(" . number_format(abs($row['total_accountgroup']), 0, ".", ",") . ")</span>";
			}

			echo "<cite>{$row['cur_shortname']}</cite>";
			if (isset($report[$row['prt_id']])) {
				$arr = $report[$row['prt_id']];
				$color = "limegreen";
				if ($arr[array_key_last($arr)] < $arr[array_key_first($arr)]) {
					$color = "tomato";
				}
				echo "<div class=\"plot\"><div>";
				echo "<svg viewBox=\"" . $curve->ViewBox(6) . "\" style=\"width:100%;\" preserveAspectRatio=\"xMidYMid slice\">";
				echo "<line " . ($curve->XMLHorizontalAxis(7)) . " stroke=\"#999\" fill=\"transparent\" stroke-width=\"1\"  shape-rendering=\"geometricPrecision\" />";
				echo "<path d=\"" . $curve->XMLCurve($arr) . "\" fill=\"transparent\" shape-rendering=\"geometricPrecision\" stroke-width=\"3\" 
				stroke=\"$color\"  />";
				echo "</svg></div></div>";
			} else {
				$arr = $date_map;
				echo "<div class=\"plot\"><div>";
				echo "<svg viewBox=\"" . $curve->ViewBox(6) . "\" style=\"width:100%;\" preserveAspectRatio=\"xMidYMid slice\">";
				echo "<line " . ($curve->XMLHorizontalAxis(7)) . " stroke=\"#999\" fill=\"transparent\" stroke-width=\"1\"  shape-rendering=\"geometricPrecision\" />";
				echo "</svg></div></div>";
			}
			echo "</span>";
		}
	}

}