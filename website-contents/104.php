<?php
use System\Template\Gremium;
use System\Template\PanelNavigator\PanelStatements;

$perpage_val = 20;
$id = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : null;
$statement = new System\Finance\Transaction\Statement($app);
$read = $statement->read($id ?? 0);

if (!$app->xhttp) { ?>
	<div class="split-view">
		<div class="panel">
			<?php
			$panelNavigator = new PanelStatements($app);
			$panelNavigator->SidePanelHTML();
			?>
		</div>
		<div class="body" id="PanelNavigator-Body">
		<?php } ?>

		<?php
		if ($read) {
			$grem = new Gremium\Gremium(true);
			if (empty($read->debitor) && empty($read->creditor)) {

				$grem->header()->status(Gremium\Status::Exclamation)->prev($fs(179)->dir)->serve("<h1>{$fs()->title}</h1><cite>" . ($read ? $read->id : "") . "</cite>");
				$grem->menu()->serve("<span class=\"small-media-hide\">Requested document is forbidden</span>");
				$grem->article()->serve(
					<<<HTML
				<ul>
					<li>Permission denied or not enough privileges to proceed with this document</li>
					<li>Document is locked or out of scope</li>
					<li>Your account doesn't have premissions for neither `Creditor` and `Debitor` accounts</li>
					<li>Contact system administrator for further assistance</li>
				</ul>
				HTML
				);
				unset($grem);
			} else {

				$grem->header()->prev($fs(179)->dir)->serve("<h1>{$fs()->title}</h1><cite>" . ($read ? $read->id : "") . "</cite>");
				$grem->menu()->serve("<span class=\"small-media-hide flex\"></span><button id=\"js-input_print\" " . ($read ? "" : "disabled") . " class=\"edge-left\" tabindex=\"-1\">Print</button>");
				$grem->title()->serve("<span class=\"flex\">Statement details</span>");
				$grem->article()->open(); ?>
				<div class="form predefined">
					<label>
						<h1>Statement ID</h1>
						<div class="btn-set">
							<span>
								<?= $read->id; ?>
							</span>
						</div>
					</label>
					<label>
						<h1>Post Date</h1>
						<div class="btn-set">
							<span>
								<?= $read->dateTime->format("Y-m-d"); ?>
							</span>
						</div>
					</label>
				</div>
				<div class="form predefined">
					<label>
						<h1>Value</h1>
						<div class="btn-set">
							<span>
								<?= $read->currency->shortname . " " . number_format($read->value, 2); ?>
							</span>
						</div>
					</label>
					<label>
						<h1>Type</h1>
						<div class="btn-set">
							<span>
								<?= $read->type->name; ?>
							</span>
						</div>
					</label>
				</div>
				<div class="form predefined">
					<label>
						<h1>Beneficiary</h1>
						<div class="btn-set">
							<span>
								<?= $read->beneficiary; ?>
							</span>
						</div>
					</label>
				</div>
				<div class="form predefined">
					<label>
						<h1>Creditor</h1>
						<div class="btn-set">
							<span <?= ($app->user->account && $read->creditor && $app->user->account->id != $read->creditor->id ? "style=\"color: var(--root-font-lightcolor)\"" : "") ?>>
								<?= ($read->creditor ? "[" . $read->creditor->currency->shortname . "] " . $read->creditor->company->name . ": " . $read->creditor->name : "-"); ?>
							</span>
						</div>
						<?php if ($read->creditor->currency->id != $read->debitor->currency->id) { ?>
							<div class="btn-set">
								<span <?= ($app->user->account && $read->creditor && $app->user->account->id != $read->creditor->id ? "style=\"color: var(--root-font-lightcolor)\"" : "") ?>>
									<?= $read->creditor->currency->shortname . " " . ($read->creditor ? number_format($read->creditAmount, 2) : "-"); ?>
								</span>
							</div>
						<?php } ?>
					</label>
					<label>
						<h1>Debitor</h1>
						<div class="btn-set">
							<span <?= ($app->user->account && $read->debitor && $app->user->account->id != $read->debitor->id ? "style=\"color: var(--root-font-lightcolor)\"" : "") ?>>
								<?= ($read->debitor ? "[" . $read->debitor->currency->shortname . "] " . $read->debitor->company->name . ": " . $read->debitor->name : "-"); ?>
							</span>
						</div>
						<?php if ($read->creditor->currency->id != $read->debitor->currency->id) { ?>
							<div class="btn-set">
								<span <?= ($app->user->account && $read->debitor && $app->user->account->id != $read->debitor->id ? "style=\"color: var(--root-font-lightcolor)\"" : "") ?>>
									<?= $read->debitor->currency->shortname . " " . ($read->debitor ? number_format($read->debitAmount, 2) : "-"); ?>
								</span>
							</div>
						<?php } ?>
					</label>
				</div>
				<?php if (sizeof($read->attachments) > 0) { ?>
					<div class="form predefined">
						<label>
							<h1>Attachments</h1>
							<div style="padding:5px 10px;" class="attachments-view">
								<?php
								foreach ($read->attachments as $file) {
									echo "<a title=\"{$file->name}\" href=\"{$fs(187)->dir}?id={$file->id}&pr=v\" target=\"_blank\"><img src=\"{$fs(187)->dir}?id={$file->id}&pr=t\" /></a>";
								}
								?>
							</div>
						</label>
					</div>
				<?php } ?>


				<div class="form predefined">
					<label>
						<h1>Description</h1>
						<div style="padding:5px 10px;line-height:1.7em">
							<?= nl2br($read->description ?? ""); ?>
						</div>
					</label>
				</div>

				<?php
				$grem->getLast()->close();
				$grem->terminate();
				unset($grem);
			}
		} elseif ($id == null) {
			$grem = new Gremium\Gremium(true);
			$grem->header()->prev($fs(179)->dir)->serve("<h1>{$fs()->title}</h1>");
			$grem->menu()->serve("<span class=\"small-media-hide\">No selected documents</span>");
			unset($grem);
		} else {
			$grem = new Gremium\Gremium(true);
			$grem->header()->prev($fs(179)->dir)->serve("<h1>{$fs()->title}</h1>");
			$grem->menu()->serve("<span class=\"small-media-hide\">Requested document is not available</span>");
			$grem->article()->serve(
				<<<HTML
				<ul>
					<li>No statement selected or selected statement number is invalid</li>
					<li>Permission denied or not enough privileges to proceed with this document</li>
					<li>Contact system administrator for further assistance</li>
				</ul>
				HTML
			);
			unset($grem);
		}
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
	<script type="text/javascript" src="static/javascript/Transactions.js"></script>
	<script type="text/javascript" src="static/javascript/Navigator.js"></script>
	<script type="text/javascript" src="static/javascript/PanelNavigator.js"></script>
	<script type="text/javascript">
		let pn = new PanelNavigator();
		pn.sourceUrl = '<?= $fs(121)->dir ?>';
		pn.itemPerRequest = <?= (int) $perpage_val; ?>;
		pn.classList = ["statment-panel"];

		if(document.getElementById("js-input_btunew"))
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
				`<div><h1 class=\"description\">${data.details}</h1></div>`
				;
		}
		pn.init();
	</script>

<?php } ?>