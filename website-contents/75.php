<?php
if (isset($reportpageid)) {
	$reportpage = $tables->pagefile_info($reportpageid);
	$arroutput = array();
	$reportid = $reportpage['id'];
	$reportwidth = "100%";
	$total = 0;
	$arrout = array();

	$month = time();

	if ($r = $app->db->query("
	SELECT 
		DATE_FORMAT(ltr_ctime, '%Y-%m-%d') AS grpdate,
		UNIX_TIMESTAMP(ltr_ctime) AS ltr_ctime,
		count(DISTINCT _major.ltr_usr_id) AS pcnt,
		lsf_name,
		ltr_shift_id
	FROM
		labour_track AS _major
			JOIN
			(
				SELECT
					prt_id,ptp_id,ptp_name,prt_name,prt_color
				FROM
					`acc_accounts` 
						JOIN `acc_accounttype` ON ptp_id=prt_type
			) AS _partition ON _partition.prt_id=ltr_prt_id
			
			LEFT JOIN
				labour_shifts ON lsf_id=ltr_shift_id
			
	WHERE
		ltr_type=1 AND DATE_FORMAT(ltr_ctime,'%Y-%m') = '" . (date("Y-m", $month)) . "'  
	GROUP BY
		grpdate,ltr_shift_id
	ORDER BY
		grpdate ASC
	")) {

		if (!isset($arrout[$reportid])) {
			$arrout[$reportid] = array($reportpage['title'], array(), array());
		}
		while ($row = $r->fetch_assoc()) {

			if (!isset($arrout[$reportid][1][$row['grpdate']])) {
				$arrout[$reportid][1][$row['grpdate']] = array();
			}
			if (!isset($arrout[$reportid][2][$row['ltr_shift_id']])) {
				$arrout[$reportid][2][$row['ltr_shift_id']] = $row['lsf_name'] == null ? "[No Shift]" : $row['lsf_name'];
			}
			$arrout[$reportid][1][$row['grpdate']][$row['ltr_shift_id']] = $row['pcnt'];
		}
	}

	$arrout[$reportid][2]["cum"] = "Total";
	foreach ($arrout[$reportid][1] as $datek => $datev) {
		$sum = 0;
		foreach ($datev as $v) {
			$sum += $v;
		}
		$arrout[$reportid][1][$datek]["cum"] = $sum;
	}

	foreach ($arrout as $cotk => $cotv) {
		$first = null;
		$last = null;
		foreach ($cotv[1] as $datek => $datav) {
			if ($first == null) {
				$first = $datek;
			}
			$last = $datek;
		}
		if (preg_match("/^([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $first, $match)) {
			$first = mktime(0, 0, 0, $match[2], $match[3], $match[1]);
		}
		if (preg_match("/^([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $last, $match)) {
			$last = mktime(0, 0, 0, $match[2], $match[3], $match[1]);
		}
		$current = $first;
		$cnt = 0;
		while ($current <= $last) {
			if (!isset($arrout[$cotk][1][date("Y-m-d", $current)])) {
				$arrout[$cotk][1][date("Y-m-d", $current)] = array();
				foreach ($cotv[2] as $partk => $partv) {
					$arrout[$cotk][1][date("Y-m-d", $current)][$partk] = 0;
				}
			}
			$current = mktime(0, 0, 0, date("m", $current), date("d", $current) + 1, date("Y", $current));
			if ($cnt > 100) {
				break;
			}
			$cnt++;
		}
		ksort($arrout[$cotk][1]);
	}



	foreach ($arrout as $cotk => $cotv) {
		echo "<div style=\"width:$reportwidth;\" class=\"creportholder\">
	<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" class=\"cantable\">
	<tbody><tr><td style=\"text-align:center\"><h1>{$cotv[0]}</h1></td><td></td></tr><tr><td width=\"100%\">
	<div class=\"candiv\"><canvas id=\"chart{$cotk}\"></canvas></div></td><td class=\"legend\" style=\"min-width:150px;\" valign=\"middle\">";
		$cnt = 0;
		foreach ($cotv[2] as $valk => $valv) {
			echo "<div><span style=\"background-color:rgba({$colorlist[$cnt]},1);\"></span>$valv</div>";
			$cnt++;
		}
		echo "</td></tr></tbody></table></div>";
	}
?><script>
		$(document).ready(function(e) {
			<?php
			foreach ($arrout as $cotk => $cotv) {
				echo "var ctx{$cotk} = document.getElementById(\"chart{$cotk}\").getContext(\"2d\");";
				echo "var barChart{$cotk} = new Chart(ctx{$cotk}).Line({
			labels: [\"\",";
				$smart = "";
				$even = false;
				$swap = 3;
				$cnt = 0;
				if (sizeof($cotv[1]) > 30) {
					$even = true;
				}
				foreach ($cotv[1] as $valk => $valv) {
					$cnt++;
					if ($even) {
						if ($cnt >= $swap) {
							echo $smart . "\"$valk\"";
							$cnt = 0;
						} else {
							echo $smart . "\"\"";
						}
					} else {
						echo $smart . "\"$valk\"";
					}
					$smart = ",";
				}
				echo "],
			datasets: [
				";
				$smart = "";
				$cnt = 0;
				foreach ($cotv[2] as $legenedk => $legenedv) {
					echo $smart;
					echo "{";
					echo "label:\"$legenedv\",";
					echo "fillColor: \"rgba({$colorlist[$cnt]},0.1)\",";
					echo "strokeColor: \"rgba({$colorlist[$cnt]},0.7)\",";
					echo "pointColor: \"rgba({$colorlist[$cnt]},1)\",";
					echo "pointStrokeColor: \"#aaa\",";
					echo "pointHighlightFill: \"#333\",";
					echo "pointHighlightStroke: \"#333\",";
					echo "data:[0,";
					$cute = "";
					foreach ($cotv[1] as $valk => $valv) {
						echo $cute;
						if (!isset($valv[$legenedk])) {
							echo "0";
						} else {
							echo ((int)$valv[$legenedk]);
						}
						$cute = ",";
					}
					echo "]";
					echo "}";
					$smart = ",";
					$cnt++;
				}

				echo "]
		},{barStrokeWidth : 1,pointDot :true,pointDotRadius :2,datasetFill :false,bezierCurveTension :0.4,
			multiTooltipTemplate: \" <%if (value>0){%><b><%= (value) %></b><%= datasetLabel %><%}%>\",
			tooltipTemplate: \"<b><%= (value) %></b><%if (label){%><%=datasetLabel%><%}%>\",
			customTooltips:function(tooltip){setCustometooltip(tooltip);}
			
		});";
			}
			?>
		});
	</script><?php } ?>