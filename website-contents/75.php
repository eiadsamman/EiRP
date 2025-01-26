<?php
use System\Lib\Graphics\SVG\CurveRelativePositive;


if (!$app->xhttp && $app->user->company->id) {
	echo "<div class=\"full-chart\"><div class=\"chart\" data-uri=\"{$fs(75)->dir}\">";
	echo "</div></div>";
} elseif ($app->user->company->id) {

	$m = microtime(true);
	$curve        = new CurveRelativePositive(20, 100, -.6);
	$date_current = new \DateTimeImmutable();
	$date_start   = new \DateTimeImmutable("first day of this month 00:00:00");
	$date_end     = new \DateTimeImmutable("last day of this month 23:59:59");

	$date_start_shift = $date_start->modify("-2 month");
	$date_end_shift   = $date_end->modify("+2 month");

	try {
		$att_reports = new System\Controller\Individual\Attendance\Reports($app);
		$curratt     = $att_reports->OngoingAttendance($app->user->company->id);
		$ind_reports = new System\Controller\Individual\Reports($app);
		$totindv     = $ind_reports->RegisteredEmployees($app->user->company->id);
	} catch (\System\Core\Exceptions\Instance\SQLException $e) {
		$curratt = 0;
		$totindv = 0;
	}

	/* Count attendance of current month */
	$r = (
		"SELECT 
			SUM(observer.report_count) AS day_attend, 
			 sequence_days.seq_day
		FROM
			(
				SELECT DATE('{$date_start->format("Y-m-d")}' + INTERVAL (seq) DAY) AS seq_day
				FROM seq_0_to_31
				WHERE seq <= '{$date_end->format("d")}'
			) AS sequence_days 
			
			LEFT JOIN 
					(
						SELECT 
							COUNT(DISTINCT ltr_usr_id) AS report_count,
   							DATE(ltr_ctime) AS report_day, ltr_otime,ltr_ctime
						FROM 
							labour_track 
								JOIN acc_accounts ON ltr_prt_id = prt_id AND prt_company_id = {$app->user->company->id}
						WHERE
							ltr_otime IS NULL OR 
							(YEAR(ltr_ctime) = '{$date_end->format("Y")}' AND MONTH(ltr_ctime) = '{$date_end->format("m")}') OR
							(YEAR(ltr_otime) = '{$date_end->format("Y")}' AND MONTH(ltr_otime) = '{$date_end->format("m")}')

						GROUP BY
							report_day

					) AS observer ON 
						(DATE(observer.ltr_ctime) <= sequence_days.seq_day) AND 
						(sequence_days.seq_day <= DATE(observer.ltr_otime) OR observer.ltr_otime IS NULL) 
		GROUP BY
			sequence_days.seq_day
		ORDER BY
			sequence_days.seq_day ;"
	);

	//echo "<div style=\"position:fixed; inset: 0 0 0 0;z-index: 9999;\"><textarea style=\"width:400px;height:200px;\">$r</textarea></div>";

	$r = $app->db->query($r);

	/* Store count result */
	$plot_points  = array();
	$values_array = array();
	if ($r) {
		while ($row = $r->fetch_row()) {
			$plot_points[$row[1]]  = (int) $row[0];
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
		<h3>{$date_current->format("d, M Y")}</h3>";
		echo "</div>";
		echo "<div class=\"chart-icon\" style=\"color:#{$fs(75)->color}\">&#xe{$fs(75)->icon};</div>";
		echo "<div class=\"plot\">";
		echo "<svg viewBox=\"" . $curve->ViewBox($date_start->diff($date_end)->days) . "\" style=\"width:100%;\" preserveAspectRatio=\"xMidYMid slice\">";
		echo "<line " . ($curve->XMLHorizontalAxis($date_start->diff($date_end)->days)) . " stroke=\"#999\" fill=\"transparent\" stroke-width=\"1\"  shape-rendering=\"geometricPrecision\" />";
		echo "<path d=\"" . $curve->XMLCurve($plot_points) . "\" fill=\"transparent\" shape-rendering=\"geometricPrecision\" stroke-width=\"8\" stroke=\"limegreen\" />";

		$curvePoints = $curve->XMLPoints($plot_points);
		foreach ($curvePoints as $k => $v) {
			if ($values_array[$k] > 0 && $v !== null) {
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

	//echo number_format(microtime(true) - $m, 2);
}