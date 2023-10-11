<?php
use System\Personalization\DashboardReports;
use System\Template\Gremium;

$dashboard = new DashboardReports($app);
if ($app->xhttp) {
	if (isset($_POST['update'])) {
		$dashboard->setOrder($_POST['update']);
		exit;
	}
	exit;
}

$grem = new Gremium\Gremium(true);
$grem->header()->prev($fs(27)->dir)->serve("<h1>Settings</h1>");
$grem->legend()->serve("<span class=\"flex\">Dashboard reports</span>");
$grem->article()->open();

$firstocc = false;
foreach ($dashboard->list() as $dashboard) {
	if (!$firstocc) {
		echo "<table class=\"bom-table hover\" id=\"dash-table\" style=\"position:relative;\"><tbody>";
		$firstocc = true;
	}
	echo "<tr data-pageid=\"{$dashboard['trd_id']}\">";
	echo "<td style=\"min-width:34px;\" class=\"move-handle\">:::</td>";
	echo "<td class=\"checkbox\"><label><input " . ($dashboard['usrset_time'] == null ? " " : " checked=\"checked\" ") . " data-pageif=\"{$dashboard['trd_id']}\" type=\"checkbox\" /></label></td>";
	echo "<td style=\"width:100%\">{$dashboard['pfl_value']}</td>";
	echo "</tr>";
}
if ($firstocc) {
	echo "</tbody></table>";
} else {
	echo ('<ul><li>No dashboard reports available for your account</li><ul>');
}

$grem->getLast()->close();
unset($grem);

?>
<style>
	.ui-sortable-start {
		background-color: var(--color-soft-gray);
		position: relative;
		margin-left: -1px;
	}

	#dash-table>tbody>tr>td.move-handle {
		cursor: move;
		-webkit-touch-callout: none;
		-webkit-user-select: none;
		-khtml-user-select: none;
		-moz-user-select: none;
		-ms-user-select: none;
		user-select: none;
	}
</style>
<script>
	$(document).ready(function (e) {

		const fnUpdate = function () {
			const leaseObj = [];
			$("#dash-table > tbody").find("tr").each(function (index, element) {
				leaseObj.push([$(this).attr("data-pageid"), $(this).find("input[type=checkbox]").prop("checked") ? "1" : "0"]);
			});
			$.ajax({
				url: "<?= $fs()->dir; ?>",
				data: { "update": leaseObj },
				type: "POST"
			});
		}
		$("#dash-table input[type=checkbox]").on('click', function (e) {
			fnUpdate();
		});

		$("#dash-table > tbody").sortable({
			handle: '.move-handle',
			helper: 'clone',
			tolerance: 'pointer',
			axis: 'y',
			items: 'tr',
			opacity: 0.8,
			forceHelperSize: true,
			forcePlaceholderSize: true,
			start: function (event, ui) {
				ui.item.addClass("ui-sortable-start");
			},
			stop: function (event, ui) {
				ui.item.removeClass("ui-sortable-start");
			},
			update: function (event, ui) {
				ui.item.removeClass("ui-sortable-start");
				fnUpdate();
			}
		});

	});
</script>