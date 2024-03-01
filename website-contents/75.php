<?php
use System\Graphics\SVG\CurveRelativePositive;

if (!$app->xhttp && $app->user->company->id) {
	echo "<div class=\"full-chart\"><div class=\"chart\" data-uri=\"{$fs(75)->dir}\">";
	echo "</div></div>";
} elseif ($app->user->company->id) {
	$curve = new CurveRelativePositive(20, 100, -.6);
	$date_current = new \DateTimeImmutable();
	$date_start = new \DateTimeImmutable("first day of this month 00:00:00");
	$date_end = $date_start->modify("last day of this month 23:59:59");

	try {
		$att_reports = new System\Individual\Attendance\Reports($app);
		$curratt = $att_reports->OngoingAttendance($app->user->company->id);
		$ind_reports = new System\Individual\Reports($app);
		$totindv = $ind_reports->RegisteredEmployees($app->user->company->id);
	} catch (\System\Exceptions\Instance\SQLException $e) {
		$curratt = 0;
		$totindv = 0;
	}

	/* Count attendance of current month */
	$r = (
		"SELECT 
		COUNT(ltr_id), integers.pr_day 
	FROM
		(
			SELECT
				DATE('{$date_start->format("Y-m-d")}' + INTERVAL (t1 + t2 * 10) DAY) AS pr_day
			FROM
				(select 0 t1 union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t1,
				(select 0 t2 union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t2

			HAVING pr_day <= '{$date_end->format("Y-m-d")}'
		) AS integers 
		JOIN
			(SELECT comp_id FROM companies 
			JOIN user_company ON urc_usr_id=" . $app->user->info->id . " AND urc_usr_comp_id = comp_id AND comp_id ={$app->user->company->id}) AS sub_companies
			
		LEFT JOIN 
				(SELECT ltr_ctime,ltr_otime,ltr_id,prt_company_id FROM labour_track JOIN acc_accounts ON ltr_prt_id = prt_id) AS sub_location ON 
					DATE(sub_location.ltr_ctime) <= integers.pr_day AND 
					(DATE(sub_location.ltr_otime) >= '{$date_start->format("Y-m-d")}' OR sub_location.ltr_otime IS NULL) AND
					(pr_day <= DATE(sub_location.ltr_otime) OR sub_location.ltr_otime IS NULL) AND
					sub_location.prt_company_id = comp_id
	GROUP BY
		comp_id, pr_day 
	ORDER BY
		comp_id,integers.pr_day
"
	);

	$r = $app->db->query($r);

	/* Store count result */
	$plot_points = array();
	$values_array = array();
	if ($r) {
		while ($row = $r->fetch_row()) {
			$plot_points[$row[1]] = (int) $row[0];
			$values_array[$row[1]] = (int) $row[0];
		}
	}

	if (sizeof($plot_points) > 0) {
		/* Relative values */
		$valscale = $curve->prepareArray($plot_points);
		/* Clear over-date values */
		foreach ($plot_points as $k => &$v) {
			$point_date = new DateTime($k);
			if ($point_date > $date_current) {
				$v = null;
			}
		}

		echo "<div class=\"chart-title\">
		<h1>{$app->user->company->name}</h1>
		<h2>$curratt<span>$totindv</span></h2>
		<h3>{$date_end->format("d, M Y")}</h3>";
		echo "</div>";
		echo "<div class=\"chart-icon\" style=\"color:#{$fs(75)->color}\">&#xe{$fs(75)->icon};</div>";
		echo "<div class=\"plot\">";
		echo "<svg viewBox=\"" . $curve->ViewBox($date_start->diff($date_end)->days) . "\" style=\"width:100%;\" preserveAspectRatio=\"xMidYMid slice\">";
		echo "<line " . ($curve->XMLHorizontalAxis($date_start->diff($date_end)->days)) . " stroke=\"#999\" fill=\"transparent\" stroke-width=\"1\"  shape-rendering=\"geometricPrecision\" />";
		echo "<path d=\"" . $curve->XMLCurve($plot_points) . "\" fill=\"transparent\" shape-rendering=\"geometricPrecision\" stroke-width=\"8\" stroke=\"limegreen\" />";

		$curvePoints = $curve->XMLPoints($plot_points);
		foreach ($curvePoints as $k => $v) {
			if ($values_array[$k] > 0) {
				echo "<g class=\"svg-plot_point\">";
				echo "<rect x=\"0\" y=\"0\" rx=\"5\" width=\"150\" height=\"60\"></rect>";
				echo "<text x=\"10\" y=\"25\">" . ($values_array[$k]) . " Employees</text>";
				echo "<text x=\"10\" y=\"49\">$k</text>";
				echo "<circle cx=\"{$v->x}\" cy=\"{$v->y}\" r=\"10\" />";
				echo "</g>";
			}
		}
		echo "</svg></div>";
	}
}