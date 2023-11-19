<?php

use System\App;
use System\Individual\Attendance\Registration;
use System\SmartListObject;
use System\Template\Gremium;


$settings = array();

$settings['matrix'] = array();
$settings['matrix']['column'] = array();
$settings['matrix']['column']['width'] = 101;
$settings['matrix']['column']['align'] = "center";
$settings['matrix']['column']['count'] = 0;

$settings['color'] = array();
$settings['color']['timegradient'] = array();
$settings['color']['timegradient'][0] = "#f03";
$settings['color']['timegradient'][1] = "#3c77c3";
$settings['color']['timegradient'][2] = "#396";

if (isset($_POST['posubmit'])) {

	$dateFrom = false;
	$dateTo = false;
	$maxtime = 1;

	$onlyselection = isset($_POST['onlyselection']) ? 1 : 0;
	$displaysuspended = isset($_POST['displaysuspended']) ? 1 : 0;

	if (isset($_POST['dateFrom'][1]) && preg_match("/^([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $_POST['dateFrom'][1], $match)) {
		if (checkdate($match[2], $match[3], $match[1])) {
			$dateFrom = mktime(0, 0, 0, $match[2], $match[3], $match[1]);
		}
	}
	if (isset($_POST['dateTo'][1]) && preg_match("/^([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $_POST['dateTo'][1], $match)) {
		if (checkdate($match[2], $match[3], $match[1])) {
			$dateTo = mktime(23, 59, 59, $match[2], $match[3], $match[1]);
		}
	}


	if (!$dateFrom || !$dateTo) {
		header("HTTP_X_RESPONSE: INERR");
		echo "Select a valid date range";
		exit;
	}


	$dateFromCompare = new DateTime(date("Y-m-d", $dateFrom));
	$date__ToCompare = new DateTime(date("Y-m-d", $dateTo));
	$dateInterval = $dateFromCompare->diff($date__ToCompare);
	if ($dateInterval->days > 31) {
		header("HTTP_X_RESPONSE: INERR");
		echo "Date range is too big, maximum date range is 31 days";
		exit;
	}
	if ($dateFromCompare > $date__ToCompare) {
		header("HTTP_X_RESPONSE: INERR");
		echo "Selected start date must be smaller than the end date";
		exit;
	}


	header("HTTP_X_RESPONSE: SUCCESS");

	$parameters = array(
		"company" => $app->user->company->id,
		"paymethod" => isset($_POST['paymethod'][1]) && (int) $_POST['paymethod'][1] != 0 ? (int) $_POST['paymethod'][1] : null,
		"section" => isset($_POST['section'][1]) && (int) $_POST['section'][1] != 0 ? (int) $_POST['section'][1] : null,
		"job" => isset($_POST['section'][1]) && (int) $_POST['job'][1] != 0 ? (int) $_POST['job'][1] : null,


		//"shift"=>isset($_POST['shift'][1]) && (int)$_POST['shift'][1]!=0?(int)$_POST['shift'][1]:null,
		//"workingtime"=>isset($_POST['workingtime'][1]) && (int)$_POST['workingtime'][1]!=0?(int)$_POST['workingtime'][1]:null,
		//"residence"=>null,
		//"transportation"=>null,
		"onlyselection" => $onlyselection,
		"onlyselection_usr_id" => $app->user->info->id,
		"displaysuspended" => $displaysuspended,
		"limit_date" => date("Y-m-d")
	);



	$attendance = new Registration($app);
	$r = $attendance->ReportSummary(date("Y-m-d H:i:s", $dateFrom), date("Y-m-d H:i:s", $dateTo), null, $parameters);


	$arrDisplay = array();
	if ($r) {
		$cnt = 1;

		while ($row = $r->fetch_assoc()) {
			if (!isset($arrDisplay[$row['personID']])) {
				$arrDisplay[$row['personID']] = array();
				$arrDisplay[$row['personID']]['info'] = array();
				$arrDisplay[$row['personID']]['info']['id'] = $row['personID'];
				$arrDisplay[$row['personID']]['info']['name'] = $row['usr_firstname'] . " " . $row['usr_lastname'];
				$arrDisplay[$row['personID']]['info']['totalAttendedTime'] = 0;
				$arrDisplay[$row['personID']]['info']['timeGroup'] = $row['lbr_mth_name'];
				$arrDisplay[$row['personID']]['days'] = array();
			}
			$arrDisplay[$row['personID']]['days'][$row['att_date']] = $row['timeAttended'];
			$arrDisplay[$row['personID']]['info']['totalAttendedTime'] += $row['timeAttended'];

			if ($arrDisplay[$row['personID']]['info']['totalAttendedTime'] > $maxtime) {
				$maxtime = $arrDisplay[$row['personID']]['info']['totalAttendedTime'];
			}

			$cnt++;
		}


		$daysList = array();
		echo "
		<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"border-collapse: collapse;\">
			<tbody>
				<tr>
					<td>
						<table class=\"bom-table\">
							<thead>
								<tr><td>ID</td><td>Name</td><td>Total</td></tr>
							</thead>
							<tbody>";
		foreach ($arrDisplay as $id => $data) {
			$percentage_l = ceil($data['info']['totalAttendedTime'] / $maxtime * 100);
			$colorgradient = "#fff";
			if ($percentage_l <= 33) {
				$colorgradient = $settings['color']['timegradient'][0];
			} elseif ($percentage_l <= 66) {
				$colorgradient = $settings['color']['timegradient'][1];
			} else {
				$colorgradient = $settings['color']['timegradient'][2];
			}
			echo "<tr>";
			echo "<td>{$data['info']['id']}</td>";
			echo "<td>{$data['info']['name']}</td>";
			echo "<td align=\"right\" style=\"background-image:linear-gradient(0deg,rgba(0,0,0,0) 15%, var(--root-background-color) 15%), linear-gradient(90deg, $colorgradient {$percentage_l}%, transparent {$percentage_l}%);\">" . $app->formatTime($data['info']['totalAttendedTime']) . "</td>";
			echo "</tr>";
		}
		echo "
							</tbody>
						</table>
					</td>
					
					<td width=\"100%\" style=\"padding-left:5px;position:relative\">
						<div id=\"jsTimeMatrix\">
							<table class=\"bom-table\" style=\"width:auto;\">
								<thead>
									<tr>
										<td>Group</td>
										";
		$dayPlot = $dateFrom;
		while ($dayPlot < $dateTo) {
			echo "<td>" . date("Y-m-d", $dayPlot) . "</td>";
			$daysList[] = date("Y-m-d", $dayPlot);
			$dayPlot = strtotime(date("Y-m-d", $dayPlot) . ' + 1 days');
			$settings['matrix']['column']['count']++;
		}
		echo "
									</tr>
								</thead>
								<tbody>";

		foreach ($arrDisplay as $id => $data) {
			echo "<tr>";
			echo "<th align=\"center\">{$data['info']['timeGroup']}</th>";
			foreach ($daysList as $dayId => $day) {
				if (isset($data['days'][$day])) {
					echo "<td align=\"center\">" . $app->formatTime($data['days'][$day]) . "</td>";
				} else {
					echo "<td align=\"center\">-</td>";
				}
			}
			echo "</tr>";
		}

		echo "
							</tbody></table>
						<div>
					</td>
				</tr>
			</tbody>
		</table>
		<script>
			const scrollHandler = document.getElementById(\"jsTimeMatrix\");
			let scrollPosition = 0;
			scrollHandler.addEventListener(\"wheel\", (evt) => {
				evt.preventDefault();
				scrollHandler.scrollLeft += (evt.deltaY > 0? 1:-1) * {$settings['matrix']['column']['width']} ;		
			});
		</script>
		";
	}

	$exportString = "ID\tName\tTotal";
	foreach ($daysList as $dayId => $day) {
		$exportString .= "\t" . $day;
	}
	$exportString .= "\n";
	foreach ($arrDisplay as $empID => $empData) {
		$exportString .= $empID . "\t" . $empData['info']['name'] . "\t";
		$exportString .= number_format($empData['info']['totalAttendedTime'] / 3600, 2, ".", ",");

		foreach ($daysList as $dayId => $day) {
			if (isset($empData['days'][$day])) {
				$exportString .= "\t" . $app->formatTime($empData['days'][$day]);
			} else {
				$exportString .= "\t0";
			}
		}
		$exportString .= "\n";
	}


	exit;
}


if ($app->xhttp) {
	exit;
}
?>
<style type="text/css">
	.red>td {
		color: #f03;
	}

	.gray>td {
		color: #888;
	}

	.fixedsalary {
		color: #06c;
	}

	.permanent:after {
		float: left;
		font-family: icomoon;
		content: "\e62e";
		color: #fc0;
		margin-right: 5px;
	}

	.suspended:after {
		float: left;
		font-family: icomoon2;
		content: "\f00d";
		color: #f00;
		margin-right: 5px;
	}


	.p131>tbody>tr>th {
		color: #333;
		min-width: 27px;
		max-width: 27px;
		width: 27px;
		text-align: right;
	}

	.progress {
		min-width: 100px;
		vertical-align: middle;
	}

	.progress>div {
		display: inline-block;
		height: 10px;
		vertical-align: middle;
		border-right: solid 1px #fff;
	}

	.progress>div.prg01 {
		background-color: #06c;
		height: 10px;
	}

	.progress>div.prg02 {
		background-color: #f03;
	}

	.progress>div.prg03 {
		background-color: #0c6;
		height: 10px;
	}

	.progress>div.prg04 {
		background-color: #ccc;
		height: 6px;
	}

	#jsTimeMatrix {
		display: inline-block;
		position: absolute;
		top: 0px;
		left: -1px;
		right: 0px;
		bottom: 0px;
		overflow-x: hidden;
		overflow-y: hidden;
	}

	#jsTimeMatrix>table>thead>tr>td {
		min-width:
			<?php echo $settings['matrix']['column']['width']; ?>
			px;
		max-width:
			<?php echo $settings['matrix']['column']['width']; ?>
			px;
		text-align:
			<?php echo $settings['matrix']['column']['align']; ?>
		;
	}
</style>
<?php
$grem = new Gremium\Gremium(false);

$grem->header()->serve("<h1>{$fs()->title}</h1>");

$grem->menu()->open();
echo "<button id=\"jQreport\" type=\"button\">Update Report</button>";
echo "<button id=\"jQexport\" disabled=\"disabled\" type=\"button\">Export</button>";
$grem->getLast()->close();

$grem->title()->serve("<span class=\"flex\">Filter query</span>");
$grem->article()->open("660px");

$slo = new SmartListObject($app);
?>
<form action="<?= $fs(133)->dir ?>" method="post" target="_blank" id="searchform">
	<input type="hidden" name="posubmit">
	<input type="hidden" id="export_param" name="export" value="">

	<div class="form" style="max-width:315px">
		<label>
			<h1>Payment Group</h1>
			<div class="btn-set">
				<input name="paymethod" id="input-payment-method" class="flex" type="text" data-slo=":SELECT" data-list="js-data-list_paygroup" />
			</div>
		</label>
	</div>

	<div class="form" style="max-width:650px">
		<label>
			<h1>Job Group</h1>
			<div class="btn-set">
				<input name="section" class="flex" type="text" id="input-job-group" data-slo="E001" />
			</div>
		</label>
		<label>
			<h1>Job Title</h1>
			<div class="btn-set">
				<input name="job" class="flex" type="text" id="input-job-title" data-slo="E002A" />
			</div>
		</label>
	</div>

	<div class="form" style="max-width:650px">
		<label>
			<h1>From</h1>
			<div class="btn-set">
				<input type="text" class="flex" name="dateFrom" id="input-date-start" data-slo=":DATE" value="<?php echo date("Y-m-1"); ?>">
			</div>
		</label>
		<label>
			<h1>To</h1>
			<div class="btn-set">
				<input type="text" class="flex" name="dateTo" id="input-date-end" data-slo=":DATE" value="<?php echo date("Y-m-d"); ?>">
			</div>
		</label>
	</div>
</form>
<?php
$grem->getLast()->close();
echo "<br /><br />";
$grem->title()->serve("<span class=\"flex\">Query Result</span>");
$grem->article()->serve("<div id=\"jQoutput\">No requests applied</div>");
unset($grem);


?>
<iframe style="display: none;" name="iframe" id="iframe"></iframe>
<datalist id="js-data-list_paygroup">
	<?= $slo->hrPaymentMethod() ?>
</datalist>
<script>
	$(document).ready(function (e) {
		$("#input-payment-method").slo();
		$("#input-job-group").slo();
		$("#input-job-title").slo();
		$("#input-date-start").slo();
		$("#input-date-end").slo();
		var $form = $("#searchform");

		$("#jQreport").on("click", function () {
			fetch();
		});
		var fetch = function () {
			$form.attr("method", "post");
			$form.attr("action", "<?= $fs(131)->dir ?>");
			$form.attr("target", "_blank");
			overlay.show();
			$ajaxload = $.ajax({
				type: "POST",
				url: "<?php echo $fs()->dir; ?>",
				data: $("#searchform").serialize()
			}).done(function (o, textStatus, request) {
				let response = request.getResponseHeader('HTTP_X_RESPONSE');
				if (response == "SUCCESS") {
					$("#jQexport").prop("disabled", false);
					$("#jQoutput").html(o);
				} else if (response == "INERR") {
					$("#jQexport").prop("disabled", true);
					messagesys.failure(o);
				} else {
					$("#jQexport").prop("disabled", true);
					messagesys.failure("Query execution failed, try again");
				}
			}).always(function () {
				overlay.hide();
			});
		}

		$("#jQexport").on('click', function () {
			overlay.show();
			$form.attr("method", "post");
			$form.attr("action", "<?= $fs(133)->dir ?>");
			$form.attr("target", "iframe");
			$form.submit();

			setTimeout(() => {
				overlay.hide();
			}, 1000);

		});
	});
</script>