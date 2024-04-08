<?php
use System\Personalization\DashboardReports;
use System\Personalization\Personalization;
use System\Personalization\RecordsPerPage;
use System\Template\Gremium;

$salt = date("YmdHm") . session_id() . "(08#F(&Go7g32f";
$dashboard = new DashboardReports($app);
$perpage = new RecordsPerPage($app);

if ($app->xhttp) {
	if (isset($_POST['update'])) {
		$dashboard->update($_POST['update']);
		exit;
	}

	if (isset($_POST[md5($salt)])) {
		if (Personalization::purgeAllPreferences($app)) {
			echo "1";
		}
		exit;
	}

	if (isset($_POST['pagepage'])) {
		$perpage->register(null, (int) $_POST['pagepage']);
		exit;
	}
	exit;
}

$grem = new Gremium\Gremium(true);
$grem->header()->prev($fs(27)->dir)->serve("<h1>Settings</h1>");

$grem->title()->serve("<span>System settings</span>");
$grem->article()->open();

$darkmode_checked = $themeDarkMode->mode == "light" ? "" : " checked=\"checked\" ";
echo <<<HTML
<table class="bom-table hover row-selector"></body>
	<tr>
		<th class="btn-set" id="purgeReferences"><button>Clear</button></th>
		<td width="100%"><span style="white-space:wrap">Clear all activities history from the system, including company and selections<Br/>This process can't be undone</span></td>
	</tr>
	<tr>
		<td class="checkbox"><label><input id="toggle-darkmode" type="checkbox" $darkmode_checked /></label></td>
		<td width="100%"><span style="white-space:wrap">Toggle Dark Mode</span></td>
	</tr>
	<tr>
		<td><div class="btn-set"><input type="text" data-slo=":SELECT" class="flex" data-list="perpage" id="js-input_perpage" /></div></td>
		<td width="100%"><span style="white-space:wrap">Items & Records per page</span></td>
	</tr>
</body></table>
HTML;
$grem->getLast()->close();





$grem->title()->serve("<span>Overview</span>");
$grem->article()->open();
$firstocc = false;
foreach ($dashboard->overview() as $item) {
	if (!$firstocc) {
		echo "<table class=\"bom-table hover row-selector\" id=\"dash-table\" style=\"position:relative;\"><tbody>";
		$firstocc = true;
	}
	echo "<tr data-pageid=\"{$item['trd_id']}\">";
	echo "<td class=\"checkbox\"><label><input " . ($item['usrset_time'] == null ? " " : " checked=\"checked\" ") . " data-pageif=\"{$item['trd_id']}\" type=\"checkbox\" /></label></td>";
	echo "<td style=\"min-width:34px;\" class=\"move-handle\">:::</td>";
	echo "<td style=\"width:100%\">{$item['pfl_value']}</td>";
	echo "</tr>";
}
if ($firstocc) {
	echo "</tbody></table>";
} else {
	echo ('<ul><li>No dashboard reports available for your account</li><ul>');
}
$grem->getLast()->close();



$grem->title()->serve("<span>Dashboard reports</span>");
$grem->article()->open();
$firstocc = false;
foreach ($dashboard->list() as $item) {
	if (!$firstocc) {
		echo "<table class=\"bom-table hover row-selector\" id=\"dash-table\" style=\"position:relative;\"><tbody>";
		$firstocc = true;
	}
	echo "<tr data-pageid=\"{$item['trd_id']}\">";
	echo "<td class=\"checkbox\"><label><input " . ($item['usrset_time'] == null ? " " : " checked=\"checked\" ") . " data-pageif=\"{$item['trd_id']}\" type=\"checkbox\" /></label></td>";
	echo "<td style=\"min-width:34px;\" class=\"move-handle\">:::</td>";
	echo "<td style=\"width:100%\">{$item['pfl_value']}</td>";
	echo "</tr>";
}
if ($firstocc) {
	echo "</tbody></table>";
} else {
	echo ('<ul><li>No dashboard reports available for your account</li><ul>');
}
$grem->getLast()->close();

$grem->title()->serve("<span>System theme</span>");
$grem->article()->open();
echo <<<HTML
<table class="bom-table hover row-selector"><tbody>
	<tr>
		<td class="checkbox" style="min-width:38px;width:38px;"><label><input type="radio"  id="theme-default" name="theme" type="radio" checked /></label></td>
		<td>Default theme</td>
	</tr>
</tbody></table>
HTML;
$grem->getLast()->close();
unset($grem);
?>

<datalist id="perpage">
	<?php
	$globalval = $perpage->get();
	for ($i = 25; $i <= 100; $i += 25) {
		echo "<option " . ($i == $globalval ? "selected " : "") . "data-id=\"$i\">$i</option>";
	}
	?>
</datalist>

<style>
	.ui-sortable-start {
		background-color: var(--color-soft-gray);
		position: relative;
		margin-left: -1px;
	}

	.bom-table>tbody>tr>td {
		cursor: default;
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
		$("#js-input_perpage").slo({
			onselect: function (e) {
				$.ajax({
					url: "<?= $fs()->dir; ?>",
					data: { "pagepage": e.key },
					type: "POST"
				});
			}
		});
		const saveDashboard = function () {
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
		$("#dash-table input[type=checkbox]").change(function (e) {
			saveDashboard();
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
				saveDashboard();
			}
		});



		$(".row-selector > tbody > tr > td:not(:first-child)").click(function (e) {
			if (e.target.tagName == "INPUT" || e.target.tagName == "LABEL") return true;
			let o = $(this).parent().find("input[type=radio]").prop("checked", true).change();
			o = $(this).parent().find("input[type=checkbox]");
			if (o.length > 0) {
				o.prop("checked", !o.prop("checked")).change();
			}
		});



		$("#toggle-darkmode").on("change", function () {
			toggleThemeMode()
		});
		document.addEventListener("darkmode", function (e) {
			if (e.mode == "light") {
				$("#toggle-darkmode").prop("checked", false);
			} else {
				$("#toggle-darkmode").prop("checked", true);
			}
		});



		$("#purgeReferences").click(function () {
			let conf = confirm("Are you sure you want to delete all references related to your account, this action can't be undone");
			if (conf) {
				$.ajax({
					url: "<?= $fs()->dir; ?>",
					data: { "<?= md5($salt); ?>": "challenge" },
					type: "POST"
				}).done(function (o) {
					if (o == "1") {
						messagesys.success("All references deleted from the system");
					} else {
						messagesys.failure("Operation failed");
					}
				});
			}
		});
	});
</script>