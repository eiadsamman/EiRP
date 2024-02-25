<?php
use System\Individual\Attendance\Registration;
use System\Template\Gremium;


$ui_grouplist = array(
	"position" => array(array("lsc_name"), array("lsc_name"), "Job Position", "lsc_name, ltr_ctime DESC", 1),
	"title" => array(array("lsc_name", "lty_name"), array("lsc_name", "lty_name"), "Job Title", "lsc_name, lty_name, ltr_ctime DESC", 1),
	"location" => array(array("prt_name"), array("prt_name"), "Location", "ltr_ctime DESC", 2),
);
$ui_grouplist_selection = isset($_GET['group']) && key_exists($_GET['group'], $ui_grouplist) ? $_GET['group'] : "title";

$ui_view = array(
	"0" => "Card view",
	"1" => "List view"
);
$ui_view_selection = isset($_GET['view']) && key_exists($_GET['view'], $ui_view) ? $_GET['view'] : "0";

if (isset($_POST['fetch'])) {
	$attendance = new Registration($app);
	$r = $attendance->ReportOngoing(["company" => $app->user->company->id, "::order" => $ui_grouplist[$ui_grouplist_selection][3]]);

	$total = 0;
	$counter = 0;
	$posgroup = array();
	$pb = $ui_grouplist[$ui_grouplist_selection][4];
	if ($r) {
		while ($row = $r->fetch_assoc()) {
			if ($ui_grouplist_selection == null || !array_key_exists($ui_grouplist_selection, $ui_grouplist)) {
				$grp = 0;
			} else {
				$grp = $delm = "";
				foreach ($ui_grouplist[$ui_grouplist_selection][0] as $v) {
					$grp .= $delm . $row[$v];
					$delm = ": ";
				}
			}
			if (!array_key_exists($grp, $posgroup)) {
				$posgroup[$grp] = array();
				$posgroup[$grp]['count'] = 0;
				$posgroup[$grp]['title'] = "";
				$posgroup[$grp]['dataset'] = array();
				if (array_key_exists($ui_grouplist_selection, $ui_grouplist)) {
					$delm = "";
					foreach ($ui_grouplist[$ui_grouplist_selection][1] as $v) {
						$posgroup[$grp]['title'] .= $delm . $row[$v];
						$delm = ": ";
					}
				}
			}
			$posgroup[$grp]['count']++;
			$posgroup[$grp]['dataset'][] = $row;
			$total++;
		}
	}
	header("HTTP_X_OUTCOUNT: {$total}");
	$grem = new Gremium\Gremium(true, false);
	$grem->header();
	$grem->menu();
	foreach ($posgroup as $groupk => $groupv) {
		$grem->title()->serve("{$groupv['title']} : {$groupv['count']}");

		$grem->article()->options(array(/* "nobg" */))->open();
		echo "
			<table class=\"bom-table local-mediabond-table " . (isset($_GET['view']) && $_GET['view'] == "1" ? "local-force" : "") . "\">
				<tbody>";
		foreach ($groupv['dataset'] as $row) {
			$counter++;
			echo "<tr>";
			echo "<td>$counter</td>";

			echo $pb == 1 ? "" : "<td class=\"mediabond-ignore\">{$row['lsc_name']}: {$row['lty_name']}</td>";

			echo "<td class=\"employee-photo\"><div style=\"background-image:url('" . (is_null($row['up_id']) ? "user.jpg" : $fs(187)->dir . "?id={$row['up_id']}&pr=t") . "');\"></div></td>";
			echo "<td>{$row['lbr_id']} </td>";
			echo "<td class=\"emplyee-name\">{$row['usr_firstname']} {$row['usr_lastname']}</td>";
			echo "<td>{$row['ltr_ctime_date']} {$row['ltr_ctime_time']}</td>";
			echo "<td class=\"elapsed\"><span>Elapsed: </span>" . $app->formatTime($row['diff'], false) . "</td>";

			echo $pb == 2 ? "" : "<td class=\"mediabond-ignore\">{$row['prt_name']}</td>";

			echo "<td style=\"width:100%\"></td>";
			echo "</tr>";
		}
		echo "</tbody>
			</table>";
		$grem->getLast()->close();
		echo "<br /><br />";
	}
}


if ($app->xhttp) {
	exit;
}


$grem = new Gremium\Gremium(true, false);
$grem->header()->serve("<h1>{$fs()->title}</h1><cite id=\"s-output_count\"></cite>");
$grem->menu()->open();
//echo "<input type=\"text\" placeholder=\"Search...\">";
echo "<span class=\"gap\"></span>";
echo "<input type=\"text\" class=\"edge-left\" id=\"js-input_filter-list\" data-slo=\":SELECT\" placeholder=\"Group options...\" readonly style=\"width:150px;\" data-list=\"js-data-list_group\" />";
echo "<button id=\"btn-card_view\" data-state=\"$ui_view_selection\" tabindex=\"1\">" . $ui_view[$ui_view_selection] . "</button>";
$grem->getLast()->close();
echo ("<div id=\"ajax-content\"></div>");
unset($grem);

?>
<datalist id="js-data-list_group">
	<option data-id="position">Job Position</option>
	<option data-id="title">Job Title</option>
	<option data-id="location">Location</option>
</datalist>

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
		"view": "<?= $ui_view_selection ?>",
		"auth": "<?= md5(time()); ?>",
	});



	$(document).ready(function (e) {
		let currentgourp = "";
		let populate = function () {
			overlay.show();
			var $ajax = $.ajax({
				type: "POST",
				url: "<?= $fs()->dir . "/?"; ?>" + navigator.uribuild(),
				data: { "fetch": "" }
			}).done(function (output, textStatus, request) {
				let response = request.getResponseHeader('HTTP_X_OUTCOUNT');
				document.getElementById("s-output_count").textContent = response;
				overlay.hide();
				document.getElementById("ajax-content").innerHTML = output;
			}).fail(function (a, b, c) {
				messagesys.failure(c);
			}).always(function () {
				overlay.hide();
			});
		}

		$("#js-input_filter-list").slo({
			onselect: function (e) {
				navigator.historyState.group = e.hidden;
				currentgourp = navigator.historyState.group;
				history.pushState(navigator.historyState, "<?= $fs()->title; ?>", "<?= $fs()->dir; ?>" + "/?" + navigator.uribuild());
				populate();
			}
		});

		window.onpopstate = function (e) {
			navigator.historyState.view = e.state.view;
			if (navigator.historyState.view == "0") {
				$("table.local-mediabond-table").removeClass("local-force");
				$("#btn-card_view").attr("data-state", "0").text("Card view");
			} else {
				$("table.local-mediabond-table").addClass("local-force");
				$("#btn-card_view").attr("data-state", "1").text("List view");
			}

			if (currentgourp != e.state.group) {
				navigator.historyState.group = e.state.group;
				currentgourp = navigator.historyState.group;
				populate();
			}
		};

		$("#btn-card_view").on("click", function () {
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