<?php
include_once("admin/class/Template/class.template.build.php");


use System\App;
use System\Person\Attendance;
use Template\Body;


$_TEMPLATE = new Body("");
$_TEMPLATE->SetLayout(/*Sticky Title*/true,/*Command Bar*/ true,/*Sticky Frame*/ true);
$_TEMPLATE->FrameTitlesStack(true);



$ui_grouplist = array(
	"position" => array(array("lsc_name"), array("lsc_name"), "Job Position", "lsc_name, ltr_ctime DESC", 1),
	"title" => array(array("lsc_name", "lty_name"), array("lsc_name", "lty_name"), "Job Title", "lsc_name, lty_name, ltr_ctime DESC", 1),
	"location" => array(array("prt_name"), array("prt_name"), "Location", "ltr_ctime DESC", 2),
);
$ui_grouplist_selection =  isset($_GET['group']) && key_exists($_GET['group'], $ui_grouplist) ? $_GET['group'] : "position";


$ui_view = array(
	"0" => "Card view",
	"1" => "List view"
);
$ui_view_selection = isset($_GET['view']) && key_exists($_GET['view'], $ui_view) ? $_GET['view'] : "0";

if (isset($_POST['fetch'])) {

	include_once "admin/class/attendance.php";
	$_TEMPLATE->EmulateHeaders();
	$attendance = new Attendance($app);
	$r = $attendance->ReportOngoing(["company" => $USER->company->id, "::order" => $ui_grouplist[$ui_grouplist_selection][3]]);

	$total = 0;
	$counter = 0;
	$posgroup = array();
	$pb = $ui_grouplist[$ui_grouplist_selection][4];
	if ($r) {
		while ($row = $sql->fetch_assoc($r)) {
			if ($ui_grouplist_selection == null || !array_key_exists($ui_grouplist_selection, $ui_grouplist)) {
				$grp = 0;
			} else {
				$grp = $delm = "";
				foreach ($ui_grouplist[$ui_grouplist_selection][0] as $v) {
					$grp .= $delm . $row[$v];
					$delm = ": ";
				}
			}

			if (!in_array($posgroup[$grp], $posgroup)) {
				$posgroup[$grp] = array();
				$posgroup[$grp]['count'] = 0;
				$posgroup[$grp]['title'] = "";
				$delm = "";
				foreach ($ui_grouplist[$ui_grouplist_selection][1] as $v) {
					$posgroup[$grp]['title'] .= $delm . $row[$v];
					$delm = ": ";
				}
				$posgroup[$grp]['dataset'] = array();
			}
			$posgroup[$grp]['count']++;
			$posgroup[$grp]['dataset'][] = $row;
			$total++;
		}
	}
	header("HTTP_X_OUTCOUNT: {$total}");
	foreach ($posgroup as $groupk => $groupv) {
		$_TEMPLATE->NewFrameTitle("<span class=\"flex\">{$groupv['title']}</span><span>{$groupv['count']}</span>");
		echo $_TEMPLATE->NewFrameBodyStart();
		echo "
			<table class=\"bom-table local-mediabond-table " . (isset($_GET['view']) && $_GET['view'] == "1" ? "local-force" : "") . "\">
				<tbody>";
		foreach ($groupv['dataset'] as $row) {
			$counter++;
			echo "<tr>";
			echo "<td>$counter</td>";

			echo $pb == 1 ? "" : "<td class=\"mediabond-ignore\">{$row['lsc_name']}: {$row['lty_name']}</td>";

			echo "<td class=\"employee-photo\"><div style=\"background-image:url('" . (is_null($row['up_id']) ? "user.jpg" : $tables->pagefile_info(187, null, "directory") . "?id={$row['up_id']}&pr=t") . "');\"></div></td>";
			echo "<td>{$row['lbr_id']} </td>";
			echo "<td class=\"emplyee-name\">{$row['usr_firstname']} {$row['usr_lastname']}</td>";
			echo "<td>{$row['ltr_ctime_date']} {$row['ltr_ctime_time']}</td>";
			echo "<td class=\"elapsed\"><span>Elapsed: </span>" . App::formatTime($row['diff'], false) . "</td>";

			echo $pb == 2 ? "" : "<td class=\"mediabond-ignore\">{$row['prt_name']}</td>";

			echo "<td style=\"width:100%\"></td>";
			echo "</tr>";
		}
		echo "</tbody>
			</table>";

		echo $_TEMPLATE->NewFrameBodyEnd();
	}
}


if ($h__requested_with_ajax) {
	exit;
}


?>
<style>
	@keyframes placeHolderShimmer {
		0% {
			background-position: -250px 0
		}

		100% {
			background-position: 250px 0
		}
	}


	tr.employee-photo,
	td.employee-photo,
	td.elapsed>span {
		display: none;
	}


	td.employee-photo_tween>div {
		animation-duration: 2s;
		animation-fill-mode: forwards;
		animation-iteration-count: infinite;
		animation-name: placeHolderShimmer;
		animation-timing-function: linear;
		background: #f6f7f8;
		background: linear-gradient(to right, #fff 0%, #f3f3f3 50%, #fff 100%);
		background-size: 500px 120px;
		height: 120px;
		position: relative;
		overflow: hidden;
	}

	@media only screen and (max-width: 624px) {
		#btn-card_view {
			display: none;
		}

		.bom-table.local-mediabond-table>thead {
			display: none;
		}

		.bom-table.local-mediabond-table>tbody,
		.bom-table.local-mediabond-table {
			display: block;
			border: none;
		}

		td.employee-photo>div {
			display: inline-block;
			background-position: 50% 50%;
			background-size: 120px auto;
			background-repeat: no-repeat;
			height: 120px;
			width: 120px;
			border-radius: 10px;
			border: none;
		}

		.bom-table.local-mediabond-table>tbody>tr>td.elapsed {
			color: #06c;
		}

		.bom-table.local-mediabond-table>tbody>tr>td.elapsed>span {
			display: inline-block;
			color: #888;
			padding-right: 24px;
		}

		.bom-table.local-mediabond-table>tbody>tr {
			display: inline-block;
			min-width: 100px;
			border: solid 1px #e4e4e4;
			margin: 5px;
			padding: 14px;
			box-shadow: 0px 0px 5px rgba(100, 100, 100, 0.2);
			border: solid 1px #ddd;
			background: rgb(255, 255, 255);
			background: linear-gradient(180deg, rgba(255, 255, 255, 1) 52%, rgba(247, 247, 250, 1) 100%);
		}

		.bom-table.local-mediabond-table>tbody>tr>td {
			display: block;
			border: none;
			padding: 2px;
			min-height: auto;
			text-align: center;
		}

		.bom-table.local-mediabond-table>tbody>tr>td:nth-child(4) {
			padding-bottom: 16px;
		}

		.bom-table.local-mediabond-table>tbody>tr>td:nth-child(1),
		.bom-table.local-mediabond-table>tbody>tr>td:last-child {
			display: none;
		}

		.bom-table.local-mediabond-table>tbody>tr>td.mediabond-ignore {
			display: none;
		}

		.bom-table.local-mediabond-table>tbody>tr>td.emplyee-name {
			max-width: 130px;
			width: 130px;
			text-overflow: ellipsis;
			overflow-x: hidden;
		}

	}


	.bom-table.local-force>thead {
		display: none;
	}

	.bom-table.local-force>tbody,
	.bom-table.local-force {
		display: block;
		border: none;
	}

	td.employee-photo>div {
		display: inline-block;
		background-position: 50% 50%;
		background-size: 120px auto;
		background-repeat: no-repeat;
		height: 120px;
		width: 120px;
		border-radius: 10px;
		border: none;
	}

	.bom-table.local-force>tbody>tr>td.elapsed {
		color: #06c;
	}

	.bom-table.local-force>tbody>tr>td.elapsed>span {
		display: inline-block;
		color: #888;
		padding-right: 24px;
	}

	.bom-table.local-force>tbody>tr {
		display: inline-block;
		min-width: 100px;
		border: solid 1px #e4e4e4;
		margin: 5px;
		padding: 14px;
		box-shadow: 0px 0px 5px rgba(100, 100, 100, 0.2);
		border: solid 1px #ddd;
		background: rgb(255, 255, 255);
		background: linear-gradient(180deg, rgba(255, 255, 255, 1) 52%, rgba(247, 247, 250, 1) 100%);
	}

	.bom-table.local-force>tbody>tr>td {
		display: block;
		border: none;
		padding: 2px;
		min-height: auto;
		text-align: center;
	}

	.bom-table.local-force>tbody>tr>td:nth-child(4) {
		padding-bottom: 16px;
	}

	.bom-table.local-force>tbody>tr>td:nth-child(1),
	.bom-table.local-force>tbody>tr>td:last-child {
		display: none;
	}

	.bom-table.local-force>tbody>tr>td.mediabond-ignore {
		display: none;
	}

	.bom-table.local-force>tbody>tr>td.emplyee-name {
		max-width: 130px;
		width: 130px;
		text-overflow: ellipsis;
		overflow-x: hidden;
	}
</style>
<?php



$_TEMPLATE->Title($fs()->title, null, "<span id=\"s-output_count\"></span>");


echo $_TEMPLATE->CommandBarStart();
echo "<div class=\"btn-set\">";
//echo "<span>Search</span>";
//echo "<input type=\"text\">";
echo "<span class=\"gap\"></span>";
//echo "<button tabindex=\"1\">Export</button>";
//echo "<button tabindex=\"2\">Print</button>";
echo "<button id=\"btn-card_view\" data-state=\"$ui_view_selection\" tabindex=\"1\">" . $ui_view[$ui_view_selection] . "</button>";
echo "</div>";
echo $_TEMPLATE->CommandBarEnd();




echo "<div id=\"ajax-content\">";
echo "</div>";


?>
<script type="text/javascript">
	class Navigator {
		constructor(initState) {
			this.historyState = initState;
		}

		uribuild() {
			let uri = "";
			let delm = "";
			for (const [key, value] of Object.entries(this.historyState)) {
				uri += delm + key + "=" + (value == null ? "" : value);
				delm = "&";
			}
			return uri;
		}
	}
	const navigator = new Navigator({
		"group": "<?= $ui_grouplist_selection ?>",
		"view": "<?= $ui_view_selection ?>"
	});




	$(document).ready(function(e) {
		let populate = function() {
			overlay.show();
			var $ajax = $.ajax({
				type: "POST",
				url: "<?= $fs()->dir . "/?"; ?>" + navigator.uribuild(),
				data: {
					"fetch": ""
				}
			}).done(function(output, textStatus, request) {
				let response = request.getResponseHeader('HTTP_X_OUTCOUNT');

				document.getElementById("s-output_count").textContent = response;
				overlay.hide();
				document.getElementById("ajax-content").innerHTML = output;

			}).fail(function(a, b, c) {
				messagesys.failure(c);
			}).always(function() {
				overlay.hide();
			});
		}


		window.onpopstate = function(e) {
			navigator.historyState.view = e.state.view;
			if (navigator.historyState.view == "0") {
				$("table.local-mediabond-table").removeClass("local-force");
				$("#btn-card_view").attr("data-state", "0").text("Card view");
			} else {
				$("table.local-mediabond-table").addClass("local-force");
				$("#btn-card_view").attr("data-state", "1").text("List view");
			}
		};


		$("#btn-card_view").on("click", function() {
			if ($(this).attr("data-state") == "0") {
				$("table.local-mediabond-table").addClass("local-force");
				$(this).attr("data-state", "1").text("List view");
				navigator.historyState.view = "1";
				history.pushState(navigator.historyState, "<?= $fs()->title; ?>", "<?= $fs()->dir; ?>" + "/?" + navigator.uribuild());

			} else {
				$("table.local-mediabond-table").removeClass("local-force");
				$(this).attr("data-state", "0").text("Card view");
				navigator.historyState.view = "0";
				history.pushState(navigator.historyState, "<?= $fs()->title; ?>", "<?= $fs()->dir; ?>" + "/?" + navigator.uribuild());
			}

		});


		populate();
	});
</script>