<?php
include_once "admin/class/log.php";
include_once "admin/class/system.php";

use System\System;
use System\Person\Attendance;


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
		"company" => System::$_user->company->id,
		"paymethod" => isset($_POST['paymethod'][1]) && (int)$_POST['paymethod'][1] != 0 ? (int)$_POST['paymethod'][1] : null,
		"section" => isset($_POST['section'][1]) && (int)$_POST['section'][1] != 0 ? (int)$_POST['section'][1] : null,
		"job" => isset($_POST['section'][1]) && (int)$_POST['job'][1] != 0 ? (int)$_POST['job'][1] : null,


		//"shift"=>isset($_POST['shift'][1]) && (int)$_POST['shift'][1]!=0?(int)$_POST['shift'][1]:null,
		//"workingtime"=>isset($_POST['workingtime'][1]) && (int)$_POST['workingtime'][1]!=0?(int)$_POST['workingtime'][1]:null,
		//"residence"=>null,
		//"transportation"=>null,
		"onlyselection" => $onlyselection,
		"onlyselection_usr_id" => System::$_user->info->id,
		"displaysuspended" => $displaysuspended,
		"limit_date" => date("Y-m-d")
	);



	include_once "admin/class/attendance.php";
	$attendance = new Attendance();
	$r = $attendance->ReportSummary(date("Y-m-d H:i:s", $dateFrom), date("Y-m-d H:i:s", $dateTo), null, $parameters);


	$arrDisplay = array();
	if ($r) {
		$cnt = 1;

		while ($row = $sql->fetch_assoc($r)) {
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
			echo "<td align=\"right\" style=\"background-image:linear-gradient(0deg,rgba(0,0,0,0) 15%, #fff 15%),linear-gradient(90deg, $colorgradient {$percentage_l}%, #ffffff {$percentage_l}%);\">" . System::formatTime($data['info']['totalAttendedTime']) . "</td>";
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
					echo "<td align=\"center\">" . System::formatTime($data['days'][$day]) . "</td>";
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
			const scrollContainer = document.getElementById(\"jsTimeMatrix\");
			let scrollPosition = 0;
			scrollContainer.addEventListener(\"wheel\", (evt) => {
				evt.preventDefault();
				scrollContainer.scrollLeft += (evt.deltaY > 0? 1:-1) * {$settings['matrix']['column']['width']} ;		
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
				$exportString .= "\t" . System::formatTime($empData['days'][$day]);
			} else {
				$exportString .= "\t0";
			}
		}
		$exportString .= "\n";
	}


	exit;
}


if ($h__requested_with_ajax) {
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
		min-width: <?php echo $settings['matrix']['column']['width']; ?>px;
		max-width: <?php echo $settings['matrix']['column']['width']; ?>px;
		text-align: <?php echo $settings['matrix']['column']['align']; ?>;
	}
</style>

<?php
include_once("admin/class/Template/class.template.build.php");

use Template\TemplateBuild;

$_TEMPLATE = new TemplateBuild("Test");
$_TEMPLATE->SetLayout(/*Sticky Title*/true,/*Command Bar*/ true,/*Sticky Frame*/ true);
$_TEMPLATE->FrameTitlesStack(false);


$_TEMPLATE->Title($pageinfo['title'], null, null);

echo $_TEMPLATE->CommandBarStart();
echo "<div class=\"btn-set\">";
echo "<button id=\"jQreport\" type=\"button\">Update Report</button>";
echo "<button id=\"jQexport\" disabled=\"disabled\" type=\"button\">Export</button>";
echo "</div>";
echo $_TEMPLATE->CommandBarEnd();


$_TEMPLATE->NewFrameTitle("<span class=\"flex\">Filter query</span>");
echo $_TEMPLATE->NewFrameBodyStart();
?>

<iframe style="display: none;" name="iframe" id="iframe"></iframe>


<form action="<?php echo $tables->pagefile_info(133, null, 'directory'); ?>" method="post" target="_blank" id="searchform">
	<input type="hidden" name="posubmit">
	<input type="hidden" id="export_param" name="export" value="">
	<div class="template-gridLayout role-input">
		<div class="btn-set vertical"><span>Payment Group</span>
			<input name="paymethod" id="input-payment-method" type="text" data-slo="SALARY_PAYMENT_METHOD" />
		</div>
		<div></div>
		<div></div>
	</div>
	<div class="template-gridLayout role-input">
		<div class="btn-set vertical"><span>Job Group</span>
			<input name="section" type="text" id="input-job-group" data-slo="E001" />
		</div>
		<div class="btn-set vertical"><span>Job Title</span>
			<input name="job" type="text" id="input-job-title" data-slo="E002A" />
		</div>
		<div></div>
	</div>


	<div class="template-gridLayout role-input">
		<div class="btn-set vertical"><span>Start Date</span><input type="text" name="dateFrom" id="input-date-start" data-slo=":DATE" value="<?php echo date("Y-m-1"); ?>"></div>
		<div class="btn-set vertical"><span>End Date</span><input type="text" name="dateTo" id="input-date-end" data-slo=":DATE" value="<?php echo date("Y-m-d"); ?>"></div>
		<div></div>
	</div>
</form>
<?php
echo $_TEMPLATE->NewFrameBodyEnd();


$_TEMPLATE->NewFrameTitle("<span class=\"flex\">Query Result</span>");
echo $_TEMPLATE->NewFrameBodyStart();
?>
<div id="jQoutput"></div>
<?php
echo $_TEMPLATE->NewFrameBodyEnd();
?>

<table class="bom-table hover p131" style="display:none">
	<thead>
		<tr class="special">
			<td>#</td>
			<td title="Employee system ID">ID</td>
			<td title="Employee name (Permanent employee `stared`, Suspended employee `red x`)">Name</td>
			<td title="Job Title">Job Title</td>

			<td title="Basic salary (Fixed `blue`, derived from job title `black`)">Salary</td>
			<td title="">Variable</td>
			<td title="">Allowance</td>
			<td title="Employee time group">Time Group</td>
			<td title="Attendend days / Overtime days" colspan="2">Attendance</td>
			<!--<td title="Absent without notice / Absent with notice" colspan="2">Absence</td>
		<td title="" class="progress"></td>-->
			<td title="Attendance percentage">%</td>

		</tr>
	</thead>
	<tbody id="jQoutputX"></tbody>
</table>


<script>
	$(document).ready(function(e) {
		$("#input-payment-method").slo();
		$("#input-job-group").slo();
		$("#input-job-title").slo();
		$("#input-date-start").slo();
		$("#input-date-end").slo();
		
		
		
		var $form = $("#searchform");

		$("#jQreport").on("click", function() {
			fetch();
		});
		var fetch = function() {
			$form.attr("method", "post");
			$form.attr("action", "<?php echo $tables->pagefile_info(131, null, "directory"); ?>");
			$form.attr("target", "_blank");
			overlay.show();
			$ajaxload = $.ajax({
				type: "POST",
				url: "<?php echo $pageinfo['directory']; ?>",
				data: $("#searchform").serialize()
			}).done(function(o, textStatus, request) {
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
			}).always(function() {
				overlay.hide();
			});
		}

		$("#jQexport").on('click', function() {
			overlay.show();
			$form.attr("method", "post");
			$form.attr("action", "<?php echo $tables->pagefile_info(133, null, "directory"); ?>");
			$form.attr("target", "iframe");
			$form.submit();

			setTimeout(() => {
				overlay.hide();
			}, 1000);

		});


	});
</script>