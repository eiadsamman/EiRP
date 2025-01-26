<?php
use System\Lib\Graphics\SVG\CurveRelative;

if ($app->user->account && !$app->xhttp) {
	echo "<div class=\"account-ticket\"><div data-uri=\"{$fs(216)->dir}\">";
	echo "</div></div>";
} else {
	$curve = new CurveRelative(3, 12, -0.5);
	$d = new \DateTimeImmutable(date("Y-m-1"));
	$prev_close_date = $d->modify("-1 day");
	$prev_close_val = 0;
	if ($app->user->account) {

		
		$r = "SELECT 
				SUM(atm_value) 
			FROM 
				acc_temp
				JOIN 
					user_partition ON atm_account_id = upr_prt_id AND upr_usr_id = {$app->user->info->id} AND upr_prt_view = 1
				JOIN
					acc_main ON acm_id = atm_main
			WHERE 
				acm_rejected = 0 
				AND atm_account_id = {$app->user->account->id}
				AND acm_ctime <= \"" . $prev_close_date->format("Y-m-d") . "\" ;";
		
		$r = $app->db->query($r);
		if ($r) {
			if ($row = $r->fetch_array()) {
				$prev_close_val = $row[0];
			}
		}

		$latest_day = "";
		$report_period = [$d->format("Y-m-01"), $d->format("Y-m-t")];
		$r =
			"SELECT 
				acm_ctime,SUM(atm_value)
			FROM 
				acc_temp
				JOIN 
					user_partition ON atm_account_id = upr_prt_id AND upr_usr_id = {$app->user->info->id} AND upr_prt_view = 1
				JOIN
					acc_main ON acm_id = atm_main
			WHERE 
				acm_rejected = 0 
				AND atm_account_id = {$app->user->account->id}
				AND acm_ctime >= \"$report_period[0]\" AND acm_ctime<= \"$report_period[1]\" 
			GROUP BY
		DAY(acm_ctime);";
		$r = $app->db->query($r);
		$arr_month_vals = [];
		if ($r) {
			while ($row = $r->fetch_array()) {
				$arr_month_vals[$row[0]] = $row[1];
				$latest_day = new DateTime($row[0]);
			}
		}

		$temp_date = $d;
		$temp_val = $prev_close_val;
		$current_close_val = null;
		$array_build = [];
		$peak_value = null;
		for ($i = 1; $i <= $d->format("t"); $i++) {
			$day_id = $temp_date->format("Y-m-d");
			if ($temp_date > $latest_day) {
				$temp_val = null;
				$array_build[$temp_date->format("Y-m-d")] = null;
			} else {
				if (array_key_exists($day_id, $arr_month_vals)) {
					$temp_val += $arr_month_vals[$day_id];
				}
				$current_close_val = $temp_val;
				$array_build[$temp_date->format("Y-m-d")] = $temp_val - $prev_close_val;
			}
			$temp_date = $temp_date->modify("+1 day");
		}

		//Get peak value
		foreach ($array_build as $entry)
			if (!is_null($entry) && abs($entry) > $peak_value)
				$peak_value = abs($entry);

		//Ajdust percentages
		if ($peak_value > 0)
			foreach ($array_build as &$entry)
				if (!is_null($entry))
					$entry = $entry / $peak_value;

		echo "<div class=\"ticket-title\"><span>{$app->user->company->name}</span><br/>{$app->user->account->name}</div>";
		echo "<div class=\"ticket-value\">";
		if ($app->user->account->role->view) {
			echo ($app->user->account->balance < 0 ? "(" . number_format(abs($app->user->account->balance), 0, ".", ",") . ")" : number_format($app->user->account->balance, 0, ".", ","));
		} else {
			echo "[0.00]";
		}
		echo "<span>{$app->user->account->currency->shortname}</span>";
		echo "</div>";
		echo "<div class=\"ticket-icon\" style=\"color:#{$fs(216)->color}\">&#xe{$fs(216)->icon};</div>";
		if (is_null($current_close_val) || $current_close_val == $prev_close_val) {
			$state_color = "gray";
			$state_icon = "&#xea54;";
			$state_message = "No changes this month";
		} elseif ($current_close_val < $prev_close_val) {
			$state_color = "tomato";
			$state_icon = "&#xea43;";
			$state_message = "Decrease this month";
		} elseif ($current_close_val > $prev_close_val) {
			$state_color = "limegreen";
			$state_icon = "&#xea41;";
			$state_message = "Increase this month";
		}
		echo "<div class=\"plot\">";
		echo "<span style=\"width:90px;\"><svg 
			viewBox=\"" . $curve->ViewBox(31) . "\" 
			style=\"width:100%;\" 
			preserveAspectRatio=\"xMidYMid slice\">
			<line " . ($curve->XMLHorizontalAxis(31)) . " stroke=\"#666\" fill=\"transparent\" stroke-width=\"1\"  shape-rendering=\"geometricPrecision\" />
			<path d=\"" . $curve->XMLCurve($array_build) . "\" stroke=\"$state_color\" fill=\"transparent\" stroke-width=\"3\"  shape-rendering=\"geometricPrecision\" />
			";
		//echo $curve->XMLPoints($arr);
		echo "</svg></span>";
		echo "<div>
			<h2>$state_message </h2>
			<span class=\"state\" style=\"color:$state_color\"><i>$state_icon</i>" .
			(!is_null($current_close_val) && $prev_close_val != 0 ? number_format((($current_close_val / $prev_close_val) - 1) * 100, 1) . "%" : " 0.0%") . "
			</span>
		</div>";
		echo "</div>";
	}

}