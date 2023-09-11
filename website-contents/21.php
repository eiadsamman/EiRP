<?php
$arrayGroups = array(
	'year' => '%Y',
	'month' => '%Y-%m',
	'day' => '%Y-%m-%d',
	'hour' => '%Y-%m-%d %H:00',
	//'minute'=>'%Y-%m-%d %H:%i'
);

$groupby = null;
$hours = null;

if (isset($_POST['hours'][1]) && preg_match("/^([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $_POST['hours'][1], $match)) {
	if (checkdate($match[2], $match[3], $match[1])) {
		$hours = mktime(0, 0, 0, $match[2], $match[3], $match[1]);
		$groupby = 'hour';
	}
}
if (isset($_POST['year'][1]) && preg_match("/^([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $_POST['year'][1], $match)) {
	if (checkdate($match[2], $match[3], $match[1])) {
		$year = mktime(0, 0, 0, 1, 1, $match[1]);
		$groupby = 'month';
	}
}
if (isset($_POST['month'][1]) && preg_match("/^([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $_POST['month'][1], $match)) {
	if (checkdate($match[2], $match[3], $match[1])) {
		$month = mktime(0, 0, 0, $match[2], 1, $match[1]);
		$groupby = 'day';
	}
}
if (sizeof($_POST) == 0 || $groupby == null) {
	$_POST['month'] = array();
	$_POST['month'][1] = date("Y-m-d");
	$_POST['month'][0] = date("F ,Y");
	$month = time();
	$groupby = "day";
}


$colorlist = array(
	"70,70,70",
	"255,70,70",
	"70,255,70",
	"70,70,255",
	"255,255,70",
	"70,255,255",
	"255,70,255",
	"70,70,70",
	"255,70,70",
	"70,255,70",
	"70,70,255",
	"255,255,70",
	"70,255,255",
	"255,70,255",
	"70,70,70",
	"255,70,70",
	"70,255,70",
	"70,70,255",
	"255,255,70",
	"70,255,255",
	"255,70,255",


);

/*
$app->db->autocommit(false);
$r="
DELETE
	cobjecttrack
FROM 
	cobjecttrack 
		LEFT JOIN `acc_accounts` ON prt_id=ctr_prt_id
		JOIN
			(SELECT 
				cob_serial, cot_name,cot_id
			FROM 
				cobject
					JOIN cobjecttype ON cot_id=cob_type
			) 
			AS __cobject ON ctr_serial=__cobject.cob_serial
WHERE
	prt_id=4 AND __cobject.cot_id =1";
$app->db->query($r);
echo $app->db->affected_rows;
echo $app->db->error;
//$app->db->rollback();
$app->db->commit();
exit;
*/
?>
<script src="static/javascript/chart.js/Chart.js"></script>
<style>
	canvas {
		height: 280px;
		max-height: 280px;
		display: block;
		width: 100%;
	}

	.candiv {
		width: 100%;
		min-width: 370px;
		display: inline-block;
		padding: 20px;
	}

	.candiv>h1 {
		text-align: center;
		font-size: 1.2em;
		margin: 0;
		padding: 0;
		color: #333;
	}

	.legend {
		min-width: 200px;
		font-size: 1.1em;
		line-height: 1.5em;
	}

	.legend>div>span {
		display: inline-block;
		width: 12px;
		height: 12px;
		border-radius: 2px;
		margin-right: 10px;
	}

	.cantable {
		border: solid 1px #ccc;
		margin: 5px 0px;
	}
</style>

<form action="<?php echo $fs()->dir; ?>" method="post">
	<div class="btn-set" style="margin-bottom:5px;"><span style="min-width:160px;">Group by Months in</span><input id="year" value="<?php echo (isset($_POST['year'][0]) ? $_POST['year'][0] : ""); ?>" data-slo="YEAR" name="year" type="text" /><button>Filter</button></div>
</form>
<form action="<?php echo $fs()->dir; ?>" method="post">
	<div class="btn-set" style="margin-bottom:5px;"><span style="min-width:160px;">Group by Days in</span><input id="month" value="<?php echo (isset($_POST['month'][0]) ? $_POST['month'][0] : ""); ?>" data-slo="MONTH" name="month" type="text" /><button>Filter</button></div>
</form>
<form action="<?php echo $fs()->dir; ?>" method="post">
	<div class="btn-set" style="margin-bottom:5px;"><span style="min-width:160px;">Group by Hours in</span><input id="date" value="<?php echo (isset($_POST['hours'][0]) ? $_POST['hours'][0] : ""); ?>" data-slo="DATE" name="hours" type="text" /><button>Filter</button></div>
</form>


<hr />
<h1 style="font-size:1.5em;color:#ff3c00;text-align:center;">Filtering by <?php
																			if ($groupby == "day") {
																				echo " Days on `" . date("F ,Y", $month) . "`";
																			}
																			if ($groupby == "month") {
																				echo " Months on `" . date("Y", $year) . "`";
																			}
																			if ($groupby == "hour") {
																				echo " Hours on `" . date("F d,Y", $hours) . "`";
																			}
																			?>
</h1>
<?php
$arrout = array();
if ($r = $app->db->query("
SELECT 
	DATE_FORMAT(ctr_time, '{$arrayGroups[$groupby]}') AS grpdate,prt_id,
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
	ctr_type=1 
	" . ($groupby == 'hour' ? "AND DATE_FORMAT(ctr_time,'%Y-%m-%d') = '" . (date("Y-m-d", $hours)) . "'" : "") . "
	" . ($groupby == 'day' ? "AND DATE_FORMAT(ctr_time,'%Y-%m') = '" . (date("Y-m", $month)) . "'" : "") . "
GROUP BY
	grpdate,cot_id,prt_id
ORDER BY
	grpdate ASC
")) {
	while ($row = $r->fetch_assoc()) {
		if (!isset($arrout[$row['cot_id']])) {
			$arrout[$row['cot_id']] = array($row['cot_name'], array(), array());
		}
		if (!isset($arrout[$row['cot_id']][1][$row['grpdate']])) {
			$arrout[$row['cot_id']][1][$row['grpdate']] = array();
		}
		if (!isset($arrout[$row['cot_id']][2][$row['prt_id']])) {
			$arrout[$row['cot_id']][2][$row['prt_id']] = $row['prt_name'];
		}
		$arrout[$row['cot_id']][1][$row['grpdate']][$row['prt_id']] = $row['psum'];
	}
}


foreach ($arrout as $k => $v) {
	$arrout[$k][2]["cum"] = "Total";
	foreach ($arrout[$k][1] as $datek => $datev) {
		$sum = 0;
		foreach ($datev as $v) {
			$sum += $v;
		}
		$arrout[$k][1][$datek]["cum"] = $sum;
	}
}


if ($groupby == 'day') {
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
}
if ($groupby == 'hour') {
	foreach ($arrout as $cotk => $cotv) {
		$first = $hours;
		$last = mktime(0, 0, 0, date("m", $hours), date("d", $hours) + 1, date("Y", $hours));
		$current = $first;
		$cnt = 0;
		while ($current <= $last) {
			if (!isset($arrout[$cotk][1][date("Y-m-d H:i", $current)])) {
				$arrout[$cotk][1][date("Y-m-d H:i", $current)] = array();
				foreach ($cotv[2] as $partk => $partv) {
					$arrout[$cotk][1][date("Y-m-d H:i", $current)][$partk] = 0;
				}
			}
			$current = mktime(date("H", $current) + 1, date("i", $current), 0, date("m", $current), date("d", $current), date("Y", $current));
			if ($cnt > 100) {
				break;
			}
			$cnt++;
		}
		ksort($arrout[$cotk][1]);
	}
	foreach ($arrout as $cotk => $cotv) {
		foreach ($cotv[1] as $datek => $datev) {
			if (preg_match("/^([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1]) ([0-9]{2}):([0-9]{2})$/", $datek, $match)) {
				$time = mktime($match[4], $match[5], 0, $match[2], $match[3], $match[1]);
				$arrout[$cotk][1][date("H", $time)] = $arrout[$cotk][1][$datek];
				unset($arrout[$cotk][1][$datek]);
			}
		}
	}
}
foreach ($arrout as $cotk => $cotv) {
	echo "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" class=\"cantable\"><tbody><tr>
			<td width=\"100%\"><div class=\"candiv\"><h1>{$cotv[0]}</h1><canvas id=\"chart{$cotk}\"></canvas></div></td><td class=\"legend\" valign=\"middle\">";
	$cnt = 0;
	foreach ($cotv[2] as $legk => $legv) {
		echo "<div><span style=\"background-color:rgba({$colorlist[$cnt]},1);\"></span>$legv</div>";
		$cnt++;
	}
	echo "</td></tr></tbody></table>";
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
</script>