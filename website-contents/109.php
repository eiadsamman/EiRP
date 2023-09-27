<?php

use System\Individual\Attendance\Report;
use System\SmartListObject;
use System\Template\Gremium\Gremium;

function getAttendanceReport(&$app, $dateFrom, $dateTo, $employeeID)
{
	$attendance = new Report($app);
	$attendance->getAttendaceList($employeeID, $dateFrom, $dateTo, true, false);
	$attendance->PrintTable();
}
function CustomCheckdate($input, $state)
{
	if (preg_match("/^([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $input, $match)) {
		if (checkdate($match[2], $match[3], $match[1])) {
			if ($state == 0) {
				return mktime(00, 00, 00, $match[2], $match[3], $match[1]);
			} else {
				return mktime(23, 59, 59, $match[2], $match[3], $match[1]);
			}
		}
	}
	return false;
}

if (isset($_POST['method'], $_POST['dateTo'], $_POST['dateFrom'], $_POST['employeeID']) && $_POST['method'] == 'fetchattendance') {
	$employeeID = (int) $_POST['employeeID'];
	$dateFrom = CustomCheckdate($_POST['dateFrom'], 0) ?? time();
	$dateTo = CustomCheckdate($_POST['dateTo'], 1) ?? time();
	if ($dateFrom <= $dateTo) {
		getAttendanceReport($app, $dateFrom, $dateTo, $employeeID);
	} else {
		echo "<div class=\"btn-set\"><span>Attendace date range is not valid !</span></div>";
	}
	exit;
}



if (isset($_POST['method'], $_POST['employeeID'], $_POST['dateFrom'], $_POST['dateTo']) && $_POST['method'] == "fetchrecord") {
	$employeeID = (int) $_POST['employeeID'];
	$dateFrom = CustomCheckdate($_POST['dateFrom'], 0) ?? time();
	$dateTo = CustomCheckdate($_POST['dateTo'], 1) ?? time();


	if ($dateTo - $dateFrom > 86400 * 62) {
		echo "<div class=\"btn-set\"><span>Date range is too large, maximum allowed range is 60 days</span></div>";
		exit;
	}
	$r = $app->db->query("SELECT usr_id,CONCAT_WS(' ',COALESCE(usr_firstname,''),COALESCE(usr_lastname,'')) as user_name FROM users WHERE usr_id=$employeeID;");
	if ($r && $row = $r->fetch_assoc()) {
		if ($dateFrom <= $dateTo) {
			echo "<div id=\"jQattendaceReportOutput\">";
			getAttendanceReport($app, (int) $dateFrom, (int) $dateTo, $employeeID);
			echo "</div>";
		} else {
			echo "<div class=\"btn-set\"><span>Invalid date range, maximum allowed range is 60 days</span></div>";
		}
		exit;
	} else {
		exit;
	}
}

$SmartListObject = new SmartListObject($app);
?>
<style>
	.screen60Image {
		min-width: 320px;
		max-width: 320px;
	}

	.permanent:after {
		font-family: icomoon;
		content: "\e62e";
		display: inline-block;
		color: #fc0;
		margin-right: 7px;
	}

	.summary-report>tbody>tr>td {
		min-width: 150px;
	}
</style>

<?php
$_tmp = mktime(0, 0, 0, date("m"), 1, date("Y"));
$_tmk = mktime(0, 0, 0, date("m") + 1, 0, date("Y"));

$grem = new Gremium(true);
$grem->header()->serve("<h1>{$fs()->title}</h1>");

$grem->legend()->serve("<span class=\"flex\">Query employee attendance</span><button type=\"button\" id=\"attendanceReportSearch\">Search</button>");
$grem->article()->serve("
<table class=\"bom-table mediabond-table\" id=\"jQformTable\" style=\"margin-bottom:15px;\">
	<tbody>
		<tr>
			<th>Employee name</th>
			<td style=\"width:100%\">
				<div class=\"btn-set\" style=\"max-width:400px;\">
					<input id=\"employeIDFormSearch\" type=\"text\" data-slo=\":LIST\" data-list=\"emplist\" class=\"flex\" placeholder=\"\"  />
				</div>
			</td>
		</tr>
		<tr>
			<th>Date range</th>
			<td>
				<div class=\"btn-set\" style=\"max-width:400px;\">
					<input class=\"flex\" id=\"dateFrom\" data-slo=\":DATE\" value=\"" . date("Y-m-d", $_tmp) . "\" data-rangestart=\"2000-01-01\" type=\"text\"/>
					<input class=\"flex\" id=\"dateTo\" data-slo=\":DATE\" value=\"" . date("Y-m-d", $_tmk) . "\" data-rangestart=\"2000-01-01\" type=\"text\"/>
				</div>
			</td>
		</tr>
	</tbody>
</table>
");

$grem->article()->serve("<div id=\"jQoutput\"></div>");
$grem->terminate();

?>

<datalist id="emplist">
	<?= $SmartListObject->systemIndividual($app->user->company->id) ?>
</datalist>
<div id="jQpopupDetailsWindow"></div>

<script>
	$(document).ready(function (e) {
		var $div = $("<div />");
		$div.attr("id", "jQpopupAttDetails");
		$("#jQpopupDetailsWindow").append($div);

		var SLO_employeeID = $("#employeIDFormSearch").slo({
			onselect: function (value) {
				fn_timetable();
			},
			ondeselect: function () {
				$("#jQattendaceReportOutput").html("");
			},
			"limit": 5,
		});
		var SLO_dateFrom = $("#dateFrom").slo({
			"onselect": function () {
				fn_timetable();
			}
		});
		var SLO_dateTo = $("#dateTo").slo({
			"onselect": function () {
				fn_timetable();
			}
		});

		$("#attendanceReportSearch").on('click', function (e) {
			fn_timetable();
		});

		var $ajax = null;
		$("#jQoutput").on('mouseover', '.css_attendanceBlocks > div', function () {
			var $this = $(this);
			var idint = $(this).attr("data-clsid");
			if (idint != 0) {
				$("div[data-clsid=" + idint + "]").css({
					'background-color': 'rgba(' + $this.attr('data-clscolor') + ',0.7)'
				});
				
				$div.html(
					"<div><div><h1>" + $this.attr("data-clsprt") + "</h1></div>" +
					"<div><span>In:</span>" + $this.attr("data-clsstr") + "<br/><span>Out:</span>" + $this.attr("data-clsfin") + "</div>" +
					"<div><span>Time:</span>" + $this.attr("data-actual") + " / " + $this.attr("data-clstot") + "</div></div>"
				);
				
				let elementPosition = $("div[data-clsid=" + idint + "]").first().offset();
				elementPosition.top += $("div[data-clsid=" + idint + "]").height() - 50;
				elementPosition.left -= 200;
				
				$div.css(elementPosition);
				$div.show();
			}


		}).on('mouseout', '.css_attendanceBlocks > div', function () {
			var $this = $(this);
			var idint = $(this).attr("data-clsid");
			if (idint != 0) {
				$div.hide();
				$("div[data-clsid=" + idint + "]").css({
					'background-color': 'rgba(' + $this.attr('data-clscolor') + ',1)'
				});
			}
		});
		<?php if ($fs()->permission->edit) { ?>
				$("#jQoutput").on('clickx', '.css_attendanceBlocks > div', function () {				var idint = $(this).attr("data-clsid");				if (idint == 0) {					return				}				if ($ajax != null) {					$ajax.abort();				}				$ajax = $.ajax({					data: {						'method': 'edit',						'id': idint					},					url: "<?= $fs(78)->dir ?>",					type: "POST"				}).done(function (data) {					if (data != "") {						popup.show(data);					} else {						popup.hide();						messagesys.failure("Failed to retreive editing information")					}				}).fail(function () {					popup.hide();				});			});
		<?php } ?>

		var fn_timetable = function () {
			overlay.show();
			$.ajax({
				data: {
					'method': 'fetchrecord',
					'employeeID': SLO_employeeID.hidden[0].val(),
					'dateFrom': SLO_dateFrom.hidden[0].val(),
					'dateTo': SLO_dateTo.hidden[0].val(),
				},
				url: "<?php echo $fs()->dir; ?>",
				type: "POST"
			}).done(function (data) {
				$("#jQoutput").html(data);
			}).fail(function (a, b, c) {
				messagessys.failure(b + " - " + c);
			}).always(function () {
				overlay.hide();
			});
		}

		<?php
		if (isset($_GET['id'])) {
			$_GET['id'] = (int) $_GET['id'];
			$r = $app->db->query("SELECT CONCAT_WS(' ',COALESCE(usr_firstname,''),COALESCE(usr_lastname,'')) as user_name FROM users WHERE usr_id={$_GET['id']};");
			if ($r && $row = $r->fetch_assoc()) {
				echo 'SLO_employeeID.set("' . $_GET['id'] . '","' . stripcslashes(trim($row['user_name'])) . '");fn_timetable();';
			}
		}
		?>
		SLO_employeeID.focus();
	});
</script>