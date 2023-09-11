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
	DATE_FORMAT(ctr_time, '%Y-%m-%d') AS grpdate,prt_id,
	UNIX_TIMESTAMP(ctr_time) AS ctr_time,
	sum(ctr_qty) AS psum,count(ctr_qty) AS pcnt,
	__cobject.cot_name AS cot_name,prt_name,__cobject.cot_id
FROM 
	cobjecttrack AS _objecttrack 
		LEFT JOIN `acc_accounts` ON prt_id=ctr_prt_id
		JOIN
			(SELECT 
				cot_name,cot_id,cob_id
			FROM 
				cobject
					JOIN cobjecttype ON cot_id=cob_type
			) 
			AS __cobject ON _objecttrack.ctr_serial=__cobject.cob_id
WHERE
	ctr_type=1 AND DATE_FORMAT(ctr_time,'%Y-%m') = '" . (date("Y-m", $month)) . "'
GROUP BY
	grpdate,cot_id
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
			if (!isset($arrout[$reportid][2][$row['cot_id']])) {
				$arrout[$reportid][2][$row['cot_id']] = $row['cot_name'];
			}
			$arrout[$reportid][1][$row['grpdate']][$row['cot_id']] = $row['psum'];
		}
	}


	//Fill non-presented days
	for ($i = 1; $i <= date("j", $month); $i++) {
		if (isset($arrout[$reportid]) && isset($arrout[$reportid][1])) {
			$tempdate = mktime(0, 0, 0, date("m", $month), $i, date("Y", $month));
			if (!isset($arrout[$reportid][1][date("Y-m-d", $tempdate)])) {
				$arrout[$reportid][1][date("Y-m-d", $tempdate)] = array();
			}
		}
	}
	//After filling sort
	ksort($arrout[$reportid][1]);


	foreach ($arrout as $cotk => $cotv) {
		echo "<div style=\"width:$reportwidth;\" class=\"creportholder\">
	<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" class=\"cantable\"><tbody><tr>
			<td width=\"100%\"><div class=\"candiv\"><h1>{$cotv[0]}</h1><canvas id=\"chart{$cotk}\"></canvas></div></td>
			<td class=\"legend\" valign=\"middle\" style=\"min-width:150px;\">";
		$cnt = 0;
		foreach ($cotv[2] as $legk => $legv) {
			echo "<div><span style=\"background-color:rgba({$colorlist[$cnt]},1);\"></span>$legv</div>";
			$cnt++;
		}
		echo "</td></tr></tbody></table></div>";
	}
?>
	<div id="chartjs-tooltip"></div>
	<script>
		$(document).ready(function(e) {
			var datesel = $("#date").slo({
				"limit": 7
			});
			var yearsel = $("#year").slo({
				"limit": 7
			});
			var mon1sel = $("#month").slo({
				"limit": 7
			});
			var mon2sel = $("#month2").slo({
				"limit": 7
			});

			<?php
			foreach ($arrout as $cotk => $cotv) {
				echo "var ctx{$cotk} = document.getElementById(\"chart{$cotk}\").getContext(\"2d\");";
				echo "var barChart{$cotk} = new Chart(ctx{$cotk}).Line({
			labels: [\"\",";
				$smart = "";
				$even = false;
				$swap = 3;
				$cnt = 0;
				if (sizeof($cotv[1]) > 50) {
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
	</script>

<?php } ?>