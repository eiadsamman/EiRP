<?php
use System\Graphics\SVG\CurveRelative;

$curve = new CurveRelative(4, 2, -.5);

function randarr()
{
	$arr = [0];
	for ($i = 0; $i < 7; $i++)
		$arr[] = (float) (rand(0, 70) + 30) / 100 * (rand(0, 100) < 50 ? -1 : 1) * .9;
	return $arr;
}



$ss = microtime(true);
$ident = \System\Personalization\Identifiers::SystemCountAccountSelection->value;
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
			LIMIT 5
		) AS mua ON mua.prt_id = atm_account_id
	WHERE
		acm_rejected = 0
	GROUP BY
		mua.prt_id
	ORDER BY 
		mua.usrset_value DESC
	LIMIT 4
	;"
);

if ($r) {
	echo "<div class=\"accounts-overview\"><div class=\"tickets\">";
	$count = 0;
	while ($row = $r->fetch_assoc()) {
		$count++;
		$title = "{$row['comp_name']}, {$row['prt_name']}: " . number_format(abs($row['total_accountgroup']), 0, ".", ",") . " {$row['cur_shortname']}";


		echo "<span draggable=\"true\" title=\"$title\">";
		
		echo "<h1><div>{$row['comp_name']}</div></h1>";
		echo "<h2>{$row['prt_name']}</h2>";

		
		if ($row['total_accountgroup'] >= 0) {
			echo "<span style=\"color:var(--root-font-color);\">" . number_format(abs($row['total_accountgroup']), 0, ".", ",") . "</span>";
		} else {
			echo "<span style=\"color:var(--root-font-color);\">(" . number_format(abs($row['total_accountgroup']), 0, ".", ",") . ")</span>";
		}

		echo "<cite>{$row['cur_shortname']}</cite>";



		$arr = randarr();
		echo "<div class=\"plot\"><div>";
		echo "<svg 
			viewBox=\"" . $curve->ViewBox(sizeof($arr) - 1) . "\" 
			style=\"width:100%;\" 
			preserveAspectRatio=\"xMidYMid slice\">";
		echo "<path d=\"" . $curve->XMLCurve($arr) . "\" stroke=\"" . (($arr[sizeof($arr) - 1] > $arr[0]) ? "tomato" : "limegreen") . "\" fill=\"transparent\" shape-rendering=\"geometricPrecision\" />";
		//echo $curve->XMLPoints($arr);
		echo "</svg></div></div>";


		echo "</span>";
	}
	echo "</div></div>";
}

//https://developer.mozilla.org/en-US/docs/Web/SVG/Tutorial/Paths