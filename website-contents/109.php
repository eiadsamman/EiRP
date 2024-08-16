<?php

use System\Individual\Attendance\VisualReport;
use System\SmartListObject;
use System\Template\Gremium\Gremium;

function getAttendanceReport(&$app, $dateFrom, $dateTo, $employeeID)
{
	$attendance = new VisualReport($app);
	$attendance->getAttendaceList($employeeID, $dateFrom, $dateTo, true, false);
	$attendance->PrintTable();
}

if ($app->xhttp) {
	if (isset($_POST['method'], $_POST['dateTo'], $_POST['dateFrom'], $_POST['employeeID']) && $_POST['method'] == 'fetchattendance') {
		$employeeID = (int) $_POST['employeeID'];
		$dateFrom   = $app->dateValidate($_POST['dateFrom'], false) ?? time();
		$dateTo     = $app->dateValidate($_POST['dateTo'], true) ?? time();
		if ($dateFrom <= $dateTo) {
			getAttendanceReport($app, $dateFrom, $dateTo, $employeeID);
		} else {
			echo "Attendace date range is not valid !";
		}
		exit;
	}


	if (isset($_POST['method'], $_POST['employeeID'], $_POST['dateFrom'], $_POST['dateTo']) && $_POST['method'] == "fetchrecord") {
		$employeeID = (int) $_POST['employeeID'];
		$dateFrom   = $app->dateValidate($_POST['dateFrom'], false) ?? time();
		$dateTo     = $app->dateValidate($_POST['dateTo'], true) ?? time();


		if ($dateTo - $dateFrom > 86400 * 62) {
			echo "Date range is too large, maximum allowed range is 60 days";
			exit;
		}
		$r = $app->db->query("SELECT usr_id,CONCAT_WS(' ',COALESCE(usr_firstname,''),COALESCE(usr_lastname,'')) as user_name FROM users WHERE usr_id=$employeeID;");
		if ($r && $row = $r->fetch_assoc()) {
			if ($dateFrom <= $dateTo) {
				echo "<div id=\"jQattendaceReportOutput\">";
				getAttendanceReport($app, (int) $dateFrom, (int) $dateTo, $employeeID);
				echo "</div>";
			} else {
				echo "Invalid date range, maximum allowed range is 60 days";
			}
			exit;
		} else {
			exit;
		}
	}

	exit;
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

$grem->legend()->serve("<span class=\"flex\">Query employee attendance</span><button class=\"edge-left\" type=\"button\" id=\"attendanceReportSearch\">Search</button>");
$grem->article()->open();
echo "
<div class=\"form predefined\" id=\"jQformTable\">
	<label style=\"min-width:200px;\">
		<h1>Employee</h1>
		<div class=\"btn-set\">
			<input id=\"employeIDFormSearch\" type=\"text\" data-slo=\":LIST\" data-list=\"emplist\" class=\"flex\" placeholder=\"Employee ID or name\"  />
		</div>
	</label>
	
	<label style=\"min-width:300px;\">
		<h1>Date range</h1>
		<div class=\"btn-set\">
			<input class=\"flex\" id=\"dateFrom\" data-slo=\":DATE\" value=\"" . date("Y-m-d", $_tmp) . "\" data-rangestart=\"2000-01-01\" type=\"text\" placeholder=\"Start date\" />
			<input class=\"flex\" id=\"dateTo\" data-slo=\":DATE\" value=\"" . date("Y-m-d", $_tmk) . "\" data-rangestart=\"2000-01-01\" type=\"text\" placeholder=\"End date\" />
		</div>
	</label>
</div>
";
$grem->getLast()->close();

echo "<br />";
$grem->article()->serve("<div id=\"jQoutput\">No queries applied!<br /><br />To start chose an employee from the list above and set the desired attendance date range</div>");
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
				$("#jQoutput").html("No queries applied!<br /><br />To start chose an employee from the list above and set the desired attendance date range");
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
			$("#jQoutput").on('clickx', '.css_attendanceBlocks > div', function () { var idint = $(this).attr("data-clsid"); if (idint == 0) { return } if ($ajax != null) { $ajax.abort(); } $ajax = $.ajax({ data: { 'method': 'edit', 'id': idint }, url: "<?= $fs(78)->dir ?>", type: "POST" }).done(function (data) { if (data != "") { popup.content(data).show(); } else { popup.close(); messagesys.failure("Failed to retreive editing information") } }).fail(function () { popup.close(); }); });
		<?php } ?>

		var fn_timetable = function () {
			overlay.show();
			$.ajax({
				data: {
					'method': 'fetchrecord',
					'employeeID': SLO_employeeID[0].slo.htmlhidden.val(),
					'dateFrom': SLO_dateFrom[0].slo.htmlhidden.val(),
					'dateTo': SLO_dateTo[0].slo.htmlhidden.val(),
				},
				url: "<?= $fs()->dir; ?>",
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
			$r          = $app->db->query("SELECT CONCAT_WS(' ',COALESCE(usr_firstname,''),COALESCE(usr_lastname,'')) as user_name FROM users WHERE usr_id={$_GET['id']};");
			if ($r && $row = $r->fetch_assoc()) {
				echo 'SLO_employeeID.set("' . $_GET['id'] . '","' . stripcslashes(trim($row['user_name'])) . '");fn_timetable();';
			}
		}
		?>
		SLO_employeeID.focus();
	});
</script>