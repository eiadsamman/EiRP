<?php
if (isset($reportpageid)) {
	$arroutput = array();
	$reportid = $fs((int)$reportpageid)->id;
	$reportwidth = "50%";
	$total = 0;
	$arrout = array();

	if ($r = $app->db->query(
	"SELECT 
		COUNT(usr_id) AS cnt,
		_labourtype.lsc_name, _labourtype.lsc_color
	FROM
		labour
			JOIN users ON lbr_id = usr_id
			LEFT JOIN
				(
				SELECT
					lty_id,lsc_name,lsc_color
				FROM
					labour_type 
						JOIN labour_section ON lsc_id = lty_section
				) AS _labourtype ON _labourtype.lty_id = lbr_type
	WHERE
		lbr_resigndate IS NULL
	GROUP BY
		lsc_name
	ORDER BY
		cnt DESC
	")) {
		while ($row = $r->fetch_assoc()) {
			$row['lsc_name'] = is_null($row['lsc_name']) ? "[No job title]" : $row['lsc_name'];
			if (!isset($arrout[$reportid])) {
				$arrout[$reportid] = array($reportpage['title'], array(), array());
			}
			if (!isset($arrout[$reportid][1][$row['lsc_name']])) {
				$arrout[$reportid][1][$row['lsc_name']] = $row['cnt'];
			}
			if (!isset($arrout[$reportid][2][$row['lsc_name']])) {
				$arrout[$reportid][2][$row['lsc_name']] = is_null($row['lsc_color']) ? "cccccc" : $row['lsc_color'];
			}
			$total += $row['cnt'];
		}
	}
	echo $app->db->error;
	foreach ($arrout as $cotk => $cotv) {
		echo "<div style=\"width:$reportwidth;\" class=\"creportholder\"><table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" class=\"cantable\"><tbody><tr><td colspan=\"2\" style=\"text-align:center\"><h1>{$cotv[0]}: $total</h1></td></tr><tr><td width=\"100%\"><div class=\"candiv\"><canvas id=\"chart{$cotk}\"></canvas></div></td><td class=\"legend\" valign=\"middle\">";
		$cnt = 0;
		foreach ($cotv[1] as $valk => $valv) {
			echo "<div><span style=\"background-color:#{$arrout[$cotk][2][$valk]};\"></span><b>$valv</b> $valk</div>";
			$cnt++;
		}
		echo "</td></tr></tbody></table></div>";
	}
?><script>
		$(document).ready(function(e) {
			<?php
			foreach ($arrout as $cotk => $cotv) {
				echo "var ctx{$cotk} = document.getElementById(\"chart{$cotk}\").getContext(\"2d\");";
				echo "var barChart{$cotk} = new Chart(ctx{$cotk}).Doughnut([";
				$smart = "";
				$cnt = 0;
				foreach ($cotv[1] as $valk => $valv) {
					echo $smart;
					echo "{value: " . $valv . ",color: \"#{$arrout[$cotk][2][$valk]}\",highlight: \"#{$arrout[$cotk][2][$valk]}\",label: \"$valk\"}";
					$smart = ",";
					$cnt++;
				}
				echo "],{animateRotate :false});";
			}
			?>
		});
	</script><?php } ?>