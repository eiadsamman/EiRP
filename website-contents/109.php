<?php

include_once "admin/class/attendance-list.php";
include_once "admin/class/attendance.php";

use System\System;
use System\SLO_DataList;

function getAttendanceReport(&$sql, $dateFrom, $dateTo, $employeeID)
{
	$attendance = new AttendanceList();
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
	$employeeID = (int)$_POST['employeeID'];
	$dateFrom = CustomCheckdate($_POST['dateFrom'], 0) ?? time();
	$dateTo = CustomCheckdate($_POST['dateTo'], 1) ?? time();
	if ($dateFrom <= $dateTo) {
		getAttendanceReport($sql, $dateFrom, $dateTo, $employeeID);
	} else {
		echo "<div class=\"btn-set\"><span>Attendace date range is not valid !</span></div>";
	}
	exit;
}



if (isset($_POST['method'], $_POST['employeeID'], $_POST['dateFrom'], $_POST['dateTo']) && $_POST['method'] == "fetchrecord") {
	$employeeID = (int)$_POST['employeeID'];
	$dateFrom = CustomCheckdate($_POST['dateFrom'], 0) ?? time();
	$dateTo = CustomCheckdate($_POST['dateTo'], 1) ?? time();


	if ($dateTo - $dateFrom > 86400 * 62) {
		echo "<div class=\"btn-set\"><span>Date range is too large, maximum allowed range is 60 days</span></div>";
		exit;
	}
	$r = $sql->query("SELECT usr_id,CONCAT_WS(' ',COALESCE(usr_firstname,''),COALESCE(usr_lastname,'')) as user_name FROM users WHERE usr_id=$employeeID;");
	if ($r && $row = $sql->fetch_assoc($r)) {
		if ($dateFrom <= $dateTo) {
			echo "<div id=\"jQattendaceReportOutput\">";
			getAttendanceReport($sql, $dateFrom, $dateTo, $employeeID);
			echo "</div>";
		} else {
			echo "<div class=\"btn-set\"><span>Invalid date range, maximum allowed range is 60 days</span></div>";
		}
		exit;
	} else {
		exit;
	}
}

include_once("admin/class/slo_datalist.php");
$slo_datalist = new SLO_DataList();


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

<div id="jQpopupDetailsWindow" style="width: 0px;height: 0px;padding:0;margin:0">
</div>
<div style="position: relative;min-width:300px;max-width:800px;">
	<div style="padding-top:19px;position: sticky;top: 42px;z-index: 20;background-color: #fff;padding-bottom: 15px;">
		<div class="btn-set" style="padding-bottom:0px;background-color:#fff">
			<span class="flex">Query employee attendance</span>
			<button type="button" id="attendanceReportSearch">Search</button>
		</div>
		<span class="btn-set" style="padding:15px 0px 15px 15px;">
			<input id="employeIDFormSearch" type="text" data-slo=":LIST" data-list="emplist" class="flex" placeholder="Employee name, ID" />
			<?php
			$settings_month_start_day = false;
			$r = $sql->query("SELECT set_val FROM system_settings WHERE set_name='sal_month_start_day';");
			if ($r && $row = $sql->fetch_assoc($r)) {
				$settings_month_start_day = (int)$row['set_val'];
			}
			$_tmp = mktime(0, 0, 0, date("m"), $settings_month_start_day, date("Y"));
			$_tmk = mktime(0, 0, 0, date("m") + 1, $settings_month_start_day - 1, date("Y"));

			echo "<input id=\"dateFrom\" data-slo=\":DATE\" value=\"" . date("Y-m-d", $_tmp) . "\" data-rangestart=\"2000-01-01\" type=\"text\"/>
						<input id=\"dateTo\" data-slo=\":DATE\" value=\"" . date("Y-m-d", $_tmk) . "\" data-rangestart=\"2000-01-01\" type=\"text\"/>";
			?>
		</span>

		<div class="btn-set" style="padding-bottom:5px;background-color:#fff;padding-top: 5px;"><span class="flex"><b>Attendance report sheet</b></span></div>
	</div>
	<div id="jQoutput" style="padding-left: 15px;"></div>
</div>

<datalist id="emplist">
	<?= $slo_datalist->hr_person(System::$_user->company->id) ?>
</datalist>


<script>
	$(document).ready(function(e) {
		var $div = $("<div />");
		$div.attr("id", "jQpopupAttDetails");
		$("#jQpopupDetailsWindow").append($div);

		var SLO_employeeID = $("#employeIDFormSearch").slo({
			onselect: function(value) {
				fn_timetable();
			},
			ondeselect: function() {
				$("#jQattendaceReportOutput").html("");
			},
			"limit": 10,
		});
		var SLO_dateFrom = $("#dateFrom").slo({
			"onselect": function() {
				fn_timetable();
			}
		});
		var SLO_dateTo = $("#dateTo").slo({
			"onselect": function() {
				fn_timetable();
			}
		});

		$("#attendanceReportSearch").on('click', function(e) {
			fn_timetable();
		});

		var $ajax = null;
		$("#jQoutput").on('mouseover', '.css_attendanceBlocks > div', function() {
			var $this = $(this);
			var idint = $(this).attr("data-clsid");
			var idcld = $(this).attr("data-clscloseid");
			if (idint != 0) {
				$("div[data-clsid=" + idint + "]").css({
					'background-color': 'rgba(' + $this.attr('data-clscolor') + ',0.7)'
				});
				$div.html(
					"<div><div><h1>" + $this.attr("data-clsprt") + "</h1></div>" +
					"<div><span>In:</span>" + $this.attr("data-clsstr") + "<br/><span>Out:</span>" + $this.attr("data-clsfin") + "</div>" +
					"<div><span>Time:</span>" + $this.attr("data-actual") + " / " + $this.attr("data-clstot") + "</div></div>"
				);

				let elementPosition = $("div[data-clsid=" + idint + "]").offset();
				elementPosition.top += $("div[data-clsid=" + idint + "]").height() - 3;
				elementPosition.left -= $("#jQpopupDetailsWindow").offset().left - 15;
				$div.css(elementPosition);

				$div.show();

			}


		}).on('mouseout', '.css_attendanceBlocks > div', function() {
			var $this = $(this);
			var idint = $(this).attr("data-clsid");
			if (idint != 0) {
				$div.hide();
				$("div[data-clsid=" + idint + "]").css({
					'background-color': 'rgba(' + $this.attr('data-clscolor') + ',1)'
				});
			}
		});
		<?php if ($c__actions->edit) { ?>

			$("#jQoutput").on('clickx', '.css_attendanceBlocks > div', function() {
				var idint = $(this).attr("data-clsid");
				if (idint == 0) {
					return
				}
				if ($ajax != null) {
					$ajax.abort();
				}
				$ajax = $.ajax({
					data: {
						'method': 'edit',
						'id': idint
					},
					url: "<?php echo $tables->pagefile_info(78, null, "directory"); ?>",
					type: "POST"
				}).done(function(data) {
					if (data != "") {
						popup.show(data);
					} else {
						popup.hide();
						messagesys.failure("Failed to retreive editing information")
					}
				}).fail(function() {
					popup.hide();
				});
			});
		<?php } ?>

		var fn_timetable = function() {
			overlay.show();
			$.ajax({
				data: {
					'method': 'fetchrecord',
					'employeeID': SLO_employeeID.hidden[0].val(),
					'dateFrom': SLO_dateFrom.hidden[0].val(),
					'dateTo': SLO_dateTo.hidden[0].val(),
				},
				url: "<?php echo $pageinfo['directory']; ?>",
				type: "POST"
			}).done(function(data) {
				$("#jQoutput").html(data);
			}).fail(function(a, b, c) {
				messagessys.failure(b + " - " + c);
			}).always(function() {
				overlay.hide();
			});
		}

		<?php
		if (isset($_GET['id'])) {
			$_GET['id'] = (int)$_GET['id'];
			$r = $sql->query("SELECT CONCAT_WS(' ',COALESCE(usr_firstname,''),COALESCE(usr_lastname,'')) as user_name FROM users WHERE usr_id={$_GET['id']};");
			if ($r && $row = $sql->fetch_assoc($r)) {
				echo 'SLO_employeeID.set("' . $_GET['id'] . '","' . stripcslashes(trim($row['user_name'])) . '");fn_timetable();';
			}
		}
		?>
		SLO_employeeID.focus();
	});
</script>