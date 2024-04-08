<?php
use System\Template\Gremium;
use System\SmartListObject;
use System\Template\PanelNavigator\PanelStatements;

$accounting  = new \System\Finance\Accounting($app);
$perpage_val = 20;

$SmartListObject = new SmartListObject($app);
?>

<?php if (!$app->xhttp) { ?>
	<div class="split-view">
		<div class="panel hide">
			<?php
			$panelNavigator = new PanelStatements($app);
			$panelNavigator->SidePanelHTML();
			?>
		</div>
		<div class="body" id="PanelNavigator-Body">
		<?php } ?>

		<?php
		$grem = new Gremium\Gremium(true);
		$grem->header()->serve("<h1>{$fs()->title}</h1><cite>{$app->user->account->name}</cite>");
		$grem->article()->open();













		$controller = new System\Finance\StatementOfAccount\StatementOfAccount($app);

		$controller->criteria->setRecordsPerPage($perpage_val);
		//$controller->criteria->statementID(7207);
		//$controller->criteria->statementBeneficiary('مصطفى');
		
		$user_current = abs((int) $request['page']);
		$count        = $sum = $pages = 0;
		$controller->summary($count, $sum);
		$count = is_null($count) ? 0 : $count;
		$pages = ceil($count / $controller->criteria->getRecordsPerPage());

		$controller->criteria->setCurrentPage(1);
		if (isset($request['page']) && $user_current > 0) {
			if ($user_current > $pages) {
				$controller->criteria->setCurrentPage($pages);
			} else {
				$controller->criteria->setCurrentPage(($user_current));
			}
		} elseif (isset($request['page']) && $user_current == 0) {
			
			$controller->criteria->setCurrentPage(1);
		}
		echo "<pre>";
		echo $count . "\n";
		echo $pages . "\n";
		echo $controller->criteria->getCurrentPage() . "\n";
		echo "</pre>";

		echo "<table class=\"bom-table\"><tbody>";

		if ($count > 0) {
			$mysqli_result = $controller->chunk(false);
			if ($mysqli_result->num_rows > 0) {
				while ($row = $mysqli_result->fetch_assoc()) {
					echo "<tr>";
					echo "<td>{$row['acm_id']}</td>";
					echo "<td>" . ($row['atm_value'] <= 0 ? "(" . number_format(abs($row['atm_value']), 2) . ")" : "" . number_format(abs($row['atm_value']), 2)) . "</td>";
					echo "<td>{$row['acm_ctime']}</td>";
					echo "<td>{$row['accgrp_name']}: {$row['acccat_name']}</td>";
					echo "<td>{$row['acm_beneficial']}</td>";
					echo "<td>{$row['acm_comments']}</td>";
					echo "<td>{$row['up_count']}</td>";

					echo "</tr>";
				}
			}
		}
		echo "</tbody></table>";


































		$grem->getLast()->close();
		$grem->terminate();
		unset($grem);
		?>
		<?php if (!$app->xhttp) { ?>
		</div>
	</div>
	<div id="PanelNavigator-LoadingScreen">
		<?php
		$grem = new Gremium\Gremium(true);
		$grem->header()->serve("<span class=\"loadingScreen-placeholder header\">&nbsp;</span>");
		$grem->menu()->serve("<span class=\"\">&nbsp;</span>");
		$grem->title()->serve("<span class=\"loadingScreen-placeholder title\">&nbsp;</span>");
		$grem->article()->serve("<span class=\"loadingScreen-placeholderBody\"><span>&nbsp;</span><span>&nbsp;</span><span>&nbsp;</span><span>&nbsp;</span></span>");
		unset($grem);
		?>
	</div>
	<script type="text/javascript">
		let pageConfig = {
			method: "new",
			url: '<?= $fs()->dir ?>',
			title: '<?= $app->settings->site['title']; ?> - <?= $fs()->title ?>',
			id: <?= !empty($_GET['id']) ? (int) $_GET['id'] : "null"; ?>,
			upload: {
				url: "<?= $fs(186)->dir ?>",
				identifier: <?= \System\Attachment\Type::FinanceRecord->value; ?>
			}
		}
	</script>
	<script type="text/javascript" src="static/javascript/accounting/Transactions.js"></script>
	<script type="text/javascript" src="static/javascript/Navigator.js"></script>
	<script type="text/javascript" src="static/javascript/PanelNavigator.js"></script>
	<script type="text/javascript">
		let pn = new PanelNavigator();
		pn.sourceUrl = '<?= $fs(121)->dir ?>';
		pn.itemPerRequest = <?= (int) $perpage_val; ?>;
		pn.classList = ["statment-panel"];

		if (document.getElementById("js-input_btunew"))
			document.getElementById("js-input_btunew").addEventListener("click", function () {
				pn.clearActiveItem();
				pn.navigator.setProperty("id", null);
				pn.navigator.history_vars.method = "new";
				pn.navigator.history_vars.url = '<?= $fs(91)->dir; ?>';
				pn.navigator.history_vars.title = '<?= $app->settings->site['title']; ?> - <?= $fs(91)->title; ?>';
				pn.navigator.url = '<?= $fs(91)->dir; ?>';
				pn.loader(pn.navigator.history_vars.url, pn.navigator.history_vars.title, { "method": "new", "id": null }, () => { initInvokers() });
				pn.navigator.pushState();
			});


		pn.onclick = function (event) {
			pn.navigator.setProperty("id", event.dataset.listitem_id);
			pn.navigator.history_vars.method = "view";
			pn.navigator.history_vars.url = '<?= $fs(104)->dir; ?>';
			pn.navigator.history_vars.title = '<?= $app->settings->site['title']; ?> - <?= $fs(104)->title; ?>';
			pn.navigator.url = '<?= $fs(104)->dir; ?>';
			pn.loader(pn.navigator.history_vars.url, pn.navigator.history_vars.title, { "method": "view", "id": event.dataset.listitem_id });
			pn.navigator.pushState();
		}

		pn.listitemHandler = function (data) {
			let statementTypeIcon = data.positive ? `<span class="stm inc active"></span>` : `<span class="stm pay active"></span>`;
			let lockIcon = `<span class="stt chk"></span>`;
			let attachments = parseInt(data.attachements) > 0 ? `<span class="atch"></span>` : "";
			return `<div><h1>${data.beneficial}</h1><cite>${data.id}</cite></div>` +
				`<div><h1>${data.value}</h1><cite>${data.date}</cite></div>` +
				`<div><h1>${data.category}</h1><cite>${attachments}${statementTypeIcon}</cite></div>` +
				`<div><h1 class=\"description\">${data.details}</h1></div>`;
		}
		pn.init();
	</script>

<?php } ?>